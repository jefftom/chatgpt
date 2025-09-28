<?php

use PHPUnit\Framework\TestCase;
use WP_BetterForms\Validation\Validator;

final class ValidatorTest extends TestCase {
protected function setUp(): void {
parent::setUp();
remove_all_filters( 'wp_betterforms/inventory_remaining' );
remove_all_filters( 'wp_betterforms/validation_errors' );
}

public function test_detects_required_errors_with_condition_met(): void {
$form = [
'schema' => [
'fields' => [
[
'key'        => 'name',
'label'      => 'Name',
'required'   => true,
'conditions' => [
'enabled' => true,
'action'  => 'show',
'type'    => 'all',
'rules'   => [
[
'field'    => 'status',
'operator' => 'equals',
'value'    => 'yes',
],
],
],
],
[
'key'    => 'status',
'label'  => 'Status',
'type'   => 'select',
],
],
],
];

$result = Validator::validate_submission( $form, [ 'status' => 'yes' ] );

$this->assertInstanceOf( WP_Error::class, $result );
$errors = $result->get_error_data()['errors']['name'][0];
$this->assertSame( 'required', $errors['code'] );
}

public function test_skips_required_when_condition_not_met(): void {
$form = [
'schema' => [
'fields' => [
[
'key'        => 'name',
'label'      => 'Name',
'required'   => true,
'conditions' => [
'enabled' => true,
'action'  => 'show',
'type'    => 'all',
'rules'   => [
[
'field'    => 'status',
'operator' => 'equals',
'value'    => 'yes',
],
],
],
],
[
'key'    => 'status',
'label'  => 'Status',
'type'   => 'select',
],
],
],
];

$result = Validator::validate_submission( $form, [ 'status' => 'no' ] );

$this->assertTrue( $result );
}

public function test_inventory_limit_enforced(): void {
add_filter(
'wp_betterforms/inventory_remaining',
static fn( $remaining ) => 2
);

$form = [
'schema' => [
'fields' => [
[
'key'       => 'tickets',
'label'     => 'Tickets',
'type'      => 'number',
'inventory' => [
'enabled' => true,
],
],
],
],
];

$result = Validator::validate_submission( $form, [ 'tickets' => 3 ] );

$this->assertInstanceOf( WP_Error::class, $result );
$errors = $result->get_error_data()['errors']['tickets'][0];
$this->assertSame( 'inventory_exhausted', $errors['code'] );
$this->assertSame( 2, $errors['meta']['remaining'] );
}

public function test_calculation_mismatch_detected(): void {
$form = [
'schema' => [
'fields' => [
[
'key'         => 'total',
'label'       => 'Total',
'type'        => 'number',
'calculation' => [
'enabled' => true,
'formula' => '{{ quantity }} * {{ price }}',
],
],
[
'key' => 'quantity',
],
[
'key' => 'price',
],
],
],
];

$data   = [ 'quantity' => 2, 'price' => 10, 'total' => 15 ];
$result = Validator::validate_submission( $form, $data );

$this->assertInstanceOf( WP_Error::class, $result );
$errors = $result->get_error_data()['errors']['total'][0];
$this->assertSame( 'calculation_mismatch', $errors['code'] );
}

public function test_unique_identifier_pattern_enforced(): void {
$form = [
'schema' => [
'fields' => [
[
'key'      => 'submission_id',
'label'    => 'Submission ID',
'type'     => 'unique_id',
'uniqueId' => [
'mode' => 'uuid',
],
],
],
],
];

$result = Validator::validate_submission( $form, [ 'submission_id' => 'not-a-uuid' ] );

$this->assertInstanceOf( WP_Error::class, $result );
$errors = $result->get_error_data()['errors']['submission_id'][0];
$this->assertSame( 'invalid_uuid', $errors['code'] );
}

public function test_repeater_children_validated(): void {
$form = [
'schema' => [
'fields' => [
[
'key'      => 'guests',
'label'    => 'Guests',
'type'     => 'repeater',
'required' => true,
'fields'   => [
[
'key'      => 'name',
'label'    => 'Guest name',
'required' => true,
],
],
],
],
],
];

$data   = [ 'guests' => [ [ 'name' => '' ] ] ];
$result = Validator::validate_submission( $form, $data );

$this->assertInstanceOf( WP_Error::class, $result );
$errors = $result->get_error_data()['errors']['guests.0.name'][0];
$this->assertSame( 'required', $errors['code'] );
}
}
