<?php

namespace App\Services;

use OpenAI\Client;

class EmbeddingService
{
    protected ?Client $client = null;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key');

        if ($apiKey) {
            $this->client = \OpenAI::client($apiKey);
        }
    }

    /**
     * Generate embedding for text using OpenAI API.
     * Returns empty array if no API key is configured.
     *
     * @return array<float>
     */
    public function generateEmbedding(string $text): array
    {
        if (! $this->client) {
            return [];
        }

        try {
            $response = $this->client->embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

            return $response->embeddings[0]->embedding;
        } catch (\Exception $e) {
            report($e);

            return [];
        }
    }

    /**
     * Calculate cosine similarity between two vectors.
     *
     * @param  array<float>  $vec1
     * @param  array<float>  $vec2
     */
    public function cosineSimilarity(array $vec1, array $vec2): float
    {
        if (count($vec1) !== count($vec2)) {
            return 0;
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] ** 2;
            $magnitude2 += $vec2[$i] ** 2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 * $magnitude2 === 0) {
            return 0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }
}
