<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class UserUploadAvatarRequest extends ApiRequest
{
	public function rules (): array {
		return [
			'remove' => ['nullable', 'boolean'],
			'image'  => ['required_without_all:base64,url,remove', 'image', 'max:' . config('media-library.max_file_size')],
			'base64' => ['required_without_all:image,url,remove', 'string'],
			'url'    => ['required_without_all:image,base64,remove', 'url']
		];
	}
}