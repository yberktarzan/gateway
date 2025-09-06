<?php

return [
    'success' => [
        'default' => 'İşlem başarıyla tamamlandı',
        'created' => 'Kaynak başarıyla oluşturuldu',
        'updated' => 'Kaynak başarıyla güncellendi',
        'deleted' => 'Kaynak başarıyla silindi',
        'retrieved' => 'Veriler başarıyla alındı',
        'redirecting' => 'Yönlendiriliyor...',
    ],

    'error' => [
        'default' => 'Bir hata oluştu',
        'validation' => 'Doğrulama başarısız',
        'not_found' => 'Kaynak bulunamadı',
        'unauthorized' => 'Yetkisiz erişim',
        'forbidden' => 'Erişim yasak',
        'bad_request' => 'Hatalı istek',
        'server_error' => 'Sunucu hatası',
        'rate_limited' => 'Çok fazla istek',
        'timeout' => 'İstek zaman aşımı',
        'service_unavailable' => 'Servis geçici olarak kullanılamıyor',
    ],

    'logging' => [
        'request_logged' => 'İstek loglandı',
        'log_failed' => 'İstek loglama başarısız',
        'elastic_unavailable' => 'Elasticsearch servisi kullanılamıyor',
        'fallback_used' => 'Yedek loglama yöntemi kullanılıyor',
    ],
];
