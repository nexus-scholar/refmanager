<?php

namespace Nexus\RefManager\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'abstract' => $this->abstract,
            'doi' => $this->doi,
            'url' => $this->url,
            'journal' => $this->journal,
            'year' => $this->year,
            'keywords' => $this->keywords,
            'document_type' => $this->document_type,
            'status' => $this->status,
            'exclusion_reason' => $this->exclusion_reason,
            'merged_into_id' => $this->merged_into_id,
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,
            'pubmed_id' => $this->pubmed_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
        ];
    }
}

