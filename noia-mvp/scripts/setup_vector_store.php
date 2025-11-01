<?php
/**
 * NOIA MVP - Setup Vector Store
 * Script à exécuter UNE SEULE FOIS pour créer le Vector Store et uploader les PDFs
 *
 * Usage: php scripts/setup_vector_store.php
 */

require_once __DIR__ . '/../config/config.php';

$apiKey = OPENAI_API_KEY;

if (empty($apiKey)) {
    die("❌ OPENAI_API_KEY non définie dans .env\n");
}

echo "🚀 NOIA - Configuration du Vector Store\n";
echo "========================================\n\n";

// 1. Créer le Vector Store
echo "1️⃣ Création du Vector Store...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/vector_stores');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'OpenAI-Beta: assistants=v2'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'NOIA Base Documentaire Générale',
    'expires_after' => [
        'anchor' => 'last_active_at',
        'days' => 365
    ]
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Erreur création Vector Store: {$response}\n");
}

$vectorStore = json_decode($response, true);
$vectorStoreId = $vectorStore['id'];

echo "✅ Vector Store créé: {$vectorStoreId}\n\n";

// 2. Uploader les fichiers PDF
echo "2️⃣ Upload des fichiers PDF...\n";

$docsDir = __DIR__ . '/../docs';
$pdfFiles = glob($docsDir . '/*.pdf');

if (empty($pdfFiles)) {
    echo "⚠️ Aucun fichier PDF trouvé dans {$docsDir}\n";
    echo "   Veuillez ajouter vos documents PDF dans le dossier docs/\n\n";
} else {
    $fileIds = [];

    foreach ($pdfFiles as $pdfFile) {
        $filename = basename($pdfFile);
        echo "   📄 Upload de {$filename}... ";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/files');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file' => new CURLFile($pdfFile),
            'purpose' => 'assistants'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $file = json_decode($response, true);
            $fileIds[] = $file['id'];
            echo "✅ {$file['id']}\n";
        } else {
            echo "❌ Erreur\n";
        }
    }

    // 3. Attacher les fichiers au Vector Store
    if (!empty($fileIds)) {
        echo "\n3️⃣ Attachement des fichiers au Vector Store...\n";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/vector_stores/{$vectorStoreId}/file_batches");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'OpenAI-Beta: assistants=v2'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'file_ids' => $fileIds
        ]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            echo "✅ Fichiers attachés avec succès\n";
        } else {
            echo "❌ Erreur attachement: {$response}\n";
        }
    }
}

// 4. Créer l'Assistant
echo "\n4️⃣ Création de l'Assistant NOIA...\n";

$instructionsFile = __DIR__ . '/../docs/PROMPT_NOIA.md';
if (file_exists($instructionsFile)) {
    $instructions = file_get_contents($instructionsFile);
} else {
    $instructions = "Tu es NOIA (Nouvel Outil d'Intelligence Administrative), un assistant IA expert en administration territoriale française.

Réponds aux questions avec précision en utilisant la base documentaire fournie.

Format de réponse:
- Utilise des en-têtes Markdown (## Titre)
- Rédige des réponses détaillées (1500-2500 mots pour questions complexes)
- Cite systématiquement les sources légales (articles de loi, décrets)
- NE génère PAS d'actes/documents automatiquement (sauf demande explicite)
- Format professionnel et sobre

Structure:
## Cadre juridique
[Explication détaillée]

## Conditions d'application
[Détails]

## Procédure à suivre
[Étapes précises]

## Sources et références
[Citations légales]";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/assistants');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'OpenAI-Beta: assistants=v2'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'NOIA',
    'instructions' => $instructions,
    'model' => 'gpt-4o',
    'tools' => [
        ['type' => 'file_search']
    ],
    'tool_resources' => [
        'file_search' => [
            'vector_store_ids' => [$vectorStoreId]
        ]
    ],
    'temperature' => 0.3,
    'top_p' => 0.9
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $assistant = json_decode($response, true);
    $assistantId = $assistant['id'];
    echo "✅ Assistant créé: {$assistantId}\n\n";

    echo "========================================\n";
    echo "✅ Configuration terminée !\n\n";
    echo "📝 Ajoutez ces valeurs dans votre fichier .env:\n\n";
    echo "OPENAI_ASSISTANT_ID={$assistantId}\n";
    echo "OPENAI_VECTOR_STORE_ID={$vectorStoreId}\n\n";
    echo "🚀 Vous pouvez maintenant utiliser NOIA !\n";
} else {
    echo "❌ Erreur création Assistant: {$response}\n";
}
