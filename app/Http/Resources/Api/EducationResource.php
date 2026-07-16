<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EducationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'title' => $this->title ?? 'Edukasi',
            'category' => $this->category,
            'is_general' => (bool) $this->is_general,
            'education_date' => $this->education_date?->toDateString(),
            'education_materials' => $this->education_materials,
            'content' => $this->content,
            'youtube_url' => $this->youtube_url,
            'youtube_id' => $this->extractYoutubeId($this->youtube_url),
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'patient_understanding' => $this->patient_understanding,
            'fluid_compliance' => $this->fluid_compliance,
            'schedule_compliance' => $this->schedule_compliance,
            'follow_up_notes' => $this->follow_up_notes,
            'educator_name' => $this->educator_name,
            'created_by' => $this->created_by,
            'created_by_name' => $this->createdBy?->name,
        ];
    }

    private function extractYoutubeId(?string $url): ?string
    {
        if (!$url) return null;

        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
