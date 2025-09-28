<?php

use PHPUnit\Framework\TestCase;
use WP_BetterForms\Validation\Validator;

final class ValidatorTest extends TestCase {
public function test_detects_required_errors(): void {
$form = [
'schema' => [
'fields' => [
[
'key'      => 'email',
'label'    => 'Email',
'required' => true,
'type'     => 'email',
],
],
],
];

$result = Validator::validate_submission( $form, [] );

$this->assertInstanceOf( WP_Error::class, $result );
$this->assertArrayHasKey( 'errors', $result->get_error_data() );
}

public function test_allows_valid_submission(): void {
$form = [
'schema' => [
'fields' => [
[
'key'      => 'email',
'label'    => 'Email',
'required' => true,
'type'     => 'email',
],
],
],
];

$result = Validator::validate_submission( $form, [ 'email' => 'hello@example.com' ] );

$this->assertTrue( $result );
}
}
