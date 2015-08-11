Devstack
===================================

Validation
-------------
exceptions are from namespace \Jelito\DevStack\Mocker\Exception

- StaticMethodException - try to mock static method
- PrivateMethodException - try to mock private method
- ProtectedMethodException - try to mock protecket method
- NonExistentMethodException - try to mock unknown method
- FinalMethodException - try to mock final method
- UnknownVerifyMethodException - try to verify method which is not mocked
- UndeclaredMethodInvocationException - try to call method which is not mocked

Return values
-------------

#### Return value

```php
// create mocker, $this is instance of PHPUnit_Framework_TestCase
$mocker = new Mocker('MockBuilderTestClass', $this);

// mock method
$mocker->mockMethod('sum')->willReturn(666);

// create mocked object
$mockedObject = $mocker->createMock();

// test it
$this->assertEquals(666, $mockedObject->sum(1, 2));
```

#### Throw exception

```php
// create mocker, $this is instance of PHPUnit_Framework_TestCase
$mocker = new Mocker('MockBuilderTestClass', $this);

// mock method
$expectedException = new Exception('test');
$mocker->mockMethod('sum')->willThrow($expectedException);

// create mocked object
$mockedObject = $mocker->createMock();

// test it
try {
	$mockedObject->sum(1, 2);
} catch (Exception $actualException) {
	$this->assertSame($expectedException, $actualException);
}
```

#### Return self - return $this

```php
// create mocker, $this is instance of PHPUnit_Framework_TestCase
$mocker = new Mocker('MockBuilderTestClass', $this);

// mock method
$mocker->mockMethod('sum')->willReturnSelf();

// create mocked object
$mockedObject = $mocker->createMock();

// test it
$this->assertSame($mockedObject, $mockedObject->sum(1));
```

#### Return argument - return param passed to function

```php
// create mocker, $this is instance of PHPUnit_Framework_TestCase
$mocker = new Mocker('MockBuilderTestClass', $this);

// mock method
$mocker->mockMethod('sum')->willReturnArgument(1);

// create mocked object
$mockedObject = $mocker->createMock();

// test it
$this->assertEquals(1, $mockedObject->sum(1));
$this->assertEquals(2, $mockedObject->sum(2));
$this->assertEquals(3, $mockedObject->sum(3));
```

#### Call callback

```php
// create mocker, $this is instance of PHPUnit_Framework_TestCase
$mocker = new Mocker('MockBuilderTestClass', $this);

// mock method
$mocker->mockMethod('sum')->willCallback(function ($a, $b) {
	return $a * $b;
});

// create mocked object
$mockedObject = $mocker->createMock();

// test it
$this->assertEquals(2, $mockedObject->sum(1, 2));
$this->assertEquals(12, $mockedObject->sum(3, 4));
$this->assertEquals(30, $mockedObject->sum(5, 6));
```

Post assets
-------------

#### Number of invocations

```php
// create mocker, $this is instance of PHPUnit_Framework_TestCase
$mocker = new Mocker('MockBuilderTestClass', $this);

// mock method
$mocker->mockMethod('sum')->willReturn(666);

// create mocked object
$mockedObject = $mocker->createMock();

// test it
$mocker->verifyMethod('sum')->calledNever(); # method sum() was never called

$mockedObject->sum(1, 2);  # called first time
$mocker->verifyMethod('sum')->calledOnce();

$mockedObject->sum(3, 4);  # called second time
$mocker->verifyMethod('sum')->calledExactly(2);
```

#### Expected params of invocations

```php
// create mocker, $this is instance of PHPUnit_Framework_TestCase
$mocker = new Mocker('MockBuilderTestClass', $this);

// mock method
$mocker->mockMethod('sum')->willReturn(666);

// create mocked object
$mockedObject = $mocker->createMock();

// call it
$mockedObject->sum(1, 2);
$mockedObject->sum(3, 4);
$mockedObject->sum(5, 6);

// test it
$mocker->verifyMethod('sum')->invocationNo(1)->expectedParams(1, 2); # first call with params 1 and 2
$mocker->verifyMethod('sum')->invocationNo(2)->expectedParams(3, 4); # second call with params 3 and 4
$mocker->verifyMethod('sum')->invocationNo(3)->expectedParams(5, 6); # third call with params 5 and 6
$mocker->verifyMethod('sum')->invocationNo(-1)->expectedParams(5, 6); # last call with params 5 and 6
$mocker->verifyMethod('sum')->invocationNo(-2)->expectedParams(3, 4); # second call from end with params 3 and 4
$mocker->verifyMethod('sum')->invocationNo(-3)->expectedParams(1, 2); # third call from end with params 1 and 2
```
