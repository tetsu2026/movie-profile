<?php

return [
    /*
    |--------------------------------------------------------------------------
    | バリデーション言語行
    |--------------------------------------------------------------------------
    |
    | 以下の言語行はバリデータークラスによって使用されるデフォルトのエラー
    | メッセージです。サイズルールのように複数のバージョンがあるものがあります。
    | ここで各メッセージを自由に調整してください。
    |
    */

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeは有効なURLではありません。',
    'after' => ':attributeは:dateより後の日付を指定してください。',
    'after_or_equal' => ':attributeは:date以降の日付を指定してください。',
    'alpha' => ':attributeは文字のみ使用できます。',
    'alpha_dash' => ':attributeは文字、数字、ダッシュ、アンダースコアのみ使用できます。',
    'alpha_num' => ':attributeは文字と数字のみ使用できます。',
    'array' => ':attributeは配列である必要があります。',
    'ascii' => ':attributeは半角英数字と記号のみ使用できます。',
    'before' => ':attributeは:dateより前の日付を指定してください。',
    'before_or_equal' => ':attributeは:date以前の日付を指定してください。',
    'between' => [
        'array' => ':attributeは:min個から:max個の項目を指定してください。',
        'file' => ':attributeは:min KBから:max KBの間で指定してください。',
        'numeric' => ':attributeは:minから:maxの間で指定してください。',
        'string' => ':attributeは:min文字から:max文字の間で指定してください。',
    ],
    'boolean' => ':attributeはtrueかfalseで指定してください。',
    'can' => ':attributeに許可されていない値が含まれています。',
    'confirmed' => ':attributeの確認が一致しません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeは有効な日付ではありません。',
    'date_equals' => ':attributeは:dateと同じ日付を指定してください。',
    'date_format' => ':attributeは:format形式で指定してください。',
    'decimal' => ':attributeは小数点以下:decimal桁で指定してください。',
    'declined' => ':attributeを拒否してください。',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否してください。',
    'different' => ':attributeと:otherは異なる値を指定してください。',
    'digits' => ':attributeは:digits桁で指定してください。',
    'digits_between' => ':attributeは:min桁から:max桁の間で指定してください。',
    'dimensions' => ':attributeの画像サイズが無効です。',
    'distinct' => ':attributeに重複した値があります。',
    'doesnt_end_with' => ':attributeは次の値で終わってはいけません: :values',
    'doesnt_start_with' => ':attributeは次の値で始まってはいけません: :values',
    'email' => ':attributeは有効なメールアドレスを指定してください。',
    'ends_with' => ':attributeは次の値で終わる必要があります: :values',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'extensions' => ':attributeは次の拡張子のファイルである必要があります: :values',
    'file' => ':attributeはファイルを指定してください。',
    'filled' => ':attributeは必須です。',
    'gt' => [
        'array' => ':attributeは:value個より多い項目を指定してください。',
        'file' => ':attributeは:value KBより大きいファイルを指定してください。',
        'numeric' => ':attributeは:valueより大きい値を指定してください。',
        'string' => ':attributeは:value文字より多く指定してください。',
    ],
    'gte' => [
        'array' => ':attributeは:value個以上の項目を指定してください。',
        'file' => ':attributeは:value KB以上のファイルを指定してください。',
        'numeric' => ':attributeは:value以上の値を指定してください。',
        'string' => ':attributeは:value文字以上で指定してください。',
    ],
    'hex_color' => ':attributeは有効な16進数カラーコードを指定してください。',
    'image' => ':attributeは画像ファイルを指定してください。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeは:otherに存在しません。',
    'integer' => ':attributeは整数で指定してください。',
    'ip' => ':attributeは有効なIPアドレスを指定してください。',
    'ipv4' => ':attributeは有効なIPv4アドレスを指定してください。',
    'ipv6' => ':attributeは有効なIPv6アドレスを指定してください。',
    'json' => ':attributeは有効なJSON文字列を指定してください。',
    'lowercase' => ':attributeは小文字である必要があります。',
    'lt' => [
        'array' => ':attributeは:value個より少ない項目を指定してください。',
        'file' => ':attributeは:value KBより小さいファイルを指定してください。',
        'numeric' => ':attributeは:valueより小さい値を指定してください。',
        'string' => ':attributeは:value文字より少なく指定してください。',
    ],
    'lte' => [
        'array' => ':attributeは:value個以下の項目を指定してください。',
        'file' => ':attributeは:value KB以下のファイルを指定してください。',
        'numeric' => ':attributeは:value以下の値を指定してください。',
        'string' => ':attributeは:value文字以下で指定してください。',
    ],
    'mac_address' => ':attributeは有効なMACアドレスを指定してください。',
    'max' => [
        'array' => ':attributeは:max個以下で指定してください。',
        'file' => ':attributeは:max KB以下のファイルを指定してください。',
        'numeric' => ':attributeは:max以下で指定してください。',
        'string' => ':attributeは:max文字以下で指定してください。',
    ],
    'max_digits' => ':attributeは:max桁以下で指定してください。',
    'mimes' => ':attributeは次のファイル形式を指定してください: :values',
    'mimetypes' => ':attributeは次のファイル形式を指定してください: :values',
    'min' => [
        'array' => ':attributeは:min個以上で指定してください。',
        'file' => ':attributeは:min KB以上のファイルを指定してください。',
        'numeric' => ':attributeは:min以上で指定してください。',
        'string' => ':attributeは:min文字以上で指定してください。',
    ],
    'min_digits' => ':attributeは:min桁以上で指定してください。',
    'missing' => ':attributeは存在してはいけません。',
    'missing_if' => ':otherが:valueの場合、:attributeは存在してはいけません。',
    'missing_unless' => ':otherが:value以外の場合、:attributeは存在してはいけません。',
    'missing_with' => ':valuesが存在する場合、:attributeは存在してはいけません。',
    'missing_with_all' => ':valuesが全て存在する場合、:attributeは存在してはいけません。',
    'multiple_of' => ':attributeは:valueの倍数である必要があります。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeの形式が無効です。',
    'numeric' => ':attributeは数値で指定してください。',
    'password' => [
        'letters' => ':attributeは文字を含む必要があります。',
        'mixed' => ':attributeは大文字と小文字を含む必要があります。',
        'numbers' => ':attributeは数字を含む必要があります。',
        'symbols' => ':attributeは記号を含む必要があります。',
        'uncompromised' => ':attributeは漏洩した可能性があります。別のパスワードを選択してください。',
    ],
    'present' => ':attributeは存在する必要があります。',
    'present_if' => ':otherが:valueの場合、:attributeは存在する必要があります。',
    'present_unless' => ':otherが:value以外の場合、:attributeは存在する必要があります。',
    'present_with' => ':valuesが存在する場合、:attributeは存在する必要があります。',
    'present_with_all' => ':valuesが全て存在する場合、:attributeは存在する必要があります。',
    'prohibited' => ':attributeは禁止されています。',
    'prohibited_if' => ':otherが:valueの場合、:attributeは禁止されています。',
    'prohibited_unless' => ':otherが:value以外の場合、:attributeは禁止されています。',
    'prohibits' => ':attributeがある場合、:otherは指定できません。',
    'regex' => ':attributeの形式が無効です。',
    'required' => ':attributeは必須です。',
    'required_array_keys' => ':attributeは次のキーを含む必要があります: :values',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_if_accepted' => ':otherが承認された場合、:attributeは必須です。',
    'required_unless' => ':otherが:value以外の場合、:attributeは必須です。',
    'required_with' => ':valuesが存在する場合、:attributeは必須です。',
    'required_with_all' => ':valuesが全て存在する場合、:attributeは必須です。',
    'required_without' => ':valuesが存在しない場合、:attributeは必須です。',
    'required_without_all' => ':valuesが全て存在しない場合、:attributeは必須です。',
    'same' => ':attributeと:otherは一致する必要があります。',
    'size' => [
        'array' => ':attributeは:size個指定してください。',
        'file' => ':attributeは:size KBを指定してください。',
        'numeric' => ':attributeは:sizeを指定してください。',
        'string' => ':attributeは:size文字で指定してください。',
    ],
    'starts_with' => ':attributeは次の値で始まる必要があります: :values',
    'string' => ':attributeは文字列を指定してください。',
    'timezone' => ':attributeは有効なタイムゾーンを指定してください。',
    'unique' => ':attributeはすでに使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeは大文字である必要があります。',
    'url' => ':attributeは有効なURLを指定してください。',
    'ulid' => ':attributeは有効なULIDを指定してください。',
    'uuid' => ':attributeは有効なUUIDを指定してください。',

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション言語行
    |--------------------------------------------------------------------------
    |
    | "属性.ルール"の命名規則を使用してカスタムバリデーションメッセージを
    | 指定できます。これにより、特定の属性ルールに対して特定のカスタム
    | 言語行を簡単に指定できます。
    |
    */

    'custom' => [
        'email' => [
            'unique' => 'このメールアドレスはすでに登録されています。',
        ],
        'password' => [
            'min' => 'パスワードは:min文字以上で設定してください。',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション属性
    |--------------------------------------------------------------------------
    |
    | 以下の言語行は、"email"の代わりに"メールアドレス"のように、
    | プレースホルダーをより読みやすいものに置き換えるために使用されます。
    |
    */

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',
        'current_password' => '現在のパスワード',
        'biography' => '自己紹介',
        'thumbnail_video_id' => 'サムネイル動画',
        'original_filename' => 'ファイル名',
        'status' => 'ステータス',
    ],
];
