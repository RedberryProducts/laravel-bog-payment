<?php

use RedberryProducts\LaravelBogPayment\Traits\Utils\Arrayable;

class ArrayableDummy implements ArrayAccess
{
    use Arrayable;

    public $name;

    public $age;

    public function __construct($name = 'John Doe', $age = 30)
    {
        $this->name = $name;
        $this->age = $age;
    }
}

it('can check if an offset exists', function () {
    $object = new ArrayableDummy;

    expect(isset($object['name']))->toBeTrue();
    expect(isset($object['non_existing']))->toBeFalse();
});

it('can get and set offset values', function () {
    $object = new ArrayableDummy;

    // Get values
    expect($object['name'])->toBe('John Doe');
    expect($object['age'])->toBe(30);

    // Set values
    $object['name'] = 'Jane Doe';
    $object['age'] = 25;

    expect($object['name'])->toBe('Jane Doe');
    expect($object['age'])->toBe(25);
});

it('can unset an offset', function () {
    $object = new ArrayableDummy;

    unset($object['name']);

    expect(isset($object['name']))->toBeFalse();
});

it('can convert object to array', function () {
    $object = new ArrayableDummy('Alice', 28);

    expect($object->toArray())->toBe([
        'name' => 'Alice',
        'age' => 28,
    ]);
});
