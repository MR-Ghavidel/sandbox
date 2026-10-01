<?php

namespace App\Http\Requests;

use App\Entities\SiteEntity;
use App\Repositories\SiteRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Used both to add a site by hand and to edit one.
 */
class StoreSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_pinned' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['url' => 'آدرس', 'title' => 'عنوان', 'description' => 'توضیح'];
    }

    /**
     * A site (host) can be saved only once.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $host = SiteEntity::hostOf((string) $this->input('url'));
                $existing = $host === null || $validator->errors()->has('url') ? null : app(SiteRepository::class)->getByHosts([$host])->first();

                if ($existing !== null && $existing->id !== (int) $this->route('site')) {
                    $validator->errors()->add('url', "«{$host}» قبلاً با عنوان «{$existing->title}» ثبت شده است.");
                }
            },
        ];
    }

    /**
     * Columns to save. Without a title, the host is used.
     *
     * @return array{title: string, url: string, host: string, description: ?string, is_pinned: bool}
     */
    public function siteAttributes(): array
    {
        $url = trim($this->validated('url'));
        $host = (string) SiteEntity::hostOf($url);

        return [
            'title' => filled($this->validated('title')) ? trim($this->validated('title')) : $host,
            'url' => $url,
            'host' => $host,
            'description' => $this->validated('description'),
            'is_pinned' => $this->boolean('is_pinned'),
        ];
    }
}
