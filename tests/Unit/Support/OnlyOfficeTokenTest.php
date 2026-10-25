<?php

namespace Files13\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Files13\Support\OnlyOfficeToken;

class OnlyOfficeTokenTest extends TestCase
{
    public function test_a_token_round_trips_with_its_secret(): void
    {
        $token = OnlyOfficeToken::encode(['document' => 7, 'readonly' => false], 'sekretua');

        $this->assertSame(['document' => 7, 'readonly' => false], OnlyOfficeToken::decode($token, 'sekretua'));
    }

    public function test_another_secret_a_tampered_body_an_expired_or_unsigned_token_is_refused(): void
    {
        $token = OnlyOfficeToken::encode(['document' => 7], 'sekretua');
        [$header, , $signature] = explode('.', $token);
        $forged = $header.'.'.rtrim(strtr(base64_encode('{"document":8}'), '+/', '-_'), '=').'.'.$signature;
        $none = rtrim(strtr(base64_encode('{"alg":"none"}'), '+/', '-_'), '=').'.'.explode('.', $token)[1].'.';

        $this->assertNull(OnlyOfficeToken::decode($token, 'beste-bat'));
        $this->assertNull(OnlyOfficeToken::decode($forged, 'sekretua'));
        $this->assertNull(OnlyOfficeToken::decode($none, 'sekretua'));
        $this->assertNull(OnlyOfficeToken::decode('ez-da-tokena', 'sekretua'));
        $this->assertNull(OnlyOfficeToken::decode(OnlyOfficeToken::encode(['document' => 7, 'exp' => time() - 1], 'sekretua'), 'sekretua'));
    }
}
