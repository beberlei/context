## DEPRECATED

This library was trying to implement a nice way to implement the boundary
between application/UI and business model. It fails in my opinion, because
it offers no general theme and no guidance as to why this is useful this
exact way.

One concept that I did take away from this is service wrapping, for transactional
needs for example. This Gist shows the concept of a Transactional Doctrine ORM
Service Proxy: https://gist.github.com/3272909

It is easier to implement this yourself in your code as explicit application
boundary, rather than to use a generic library like Context though.

If you want to take a look at a much more strict approach to provide a boundary
between UI/App and Model, take a look at what could be called a successor library
[LiteCQRS](https://github.com/beberlei/litecqrs-php).

# Context

Small PHP library to help you shield your business-rules from the
controller and presentation layers. To achieve this goal the Data-Context-Interaction
and Entity-Boundary-Interceptor patterns are put to action. Context is a framework "for the model".

This library was born while trying to solve problems with todays Web/MVC applications and their
focus on the controller. Using MVC frameworks the "documented" way often leads
to tight coupling and painful reusability, testing and refactoring experience. Working
with a completly seperate service layer is tedious however, requiring lots of manual
mapping between application and model layers through Data-Transfer-Objects. Context
tries to automate this mapping process as much as possible.

Context does not interfere in your model. It offers a convenience wrapper around your model
that acts as translation mechanism between presentation layer and model.

This strict seperation does not even have to lead to overengineering and non-rapid application building.
It is very simple to build rapid prototypes on top of the context abstraction.

## Features

* Support mapping Requests (HTTP, Console, or any input for that matter) on model-request
* Encourage Observers that notify presentation layer of different model results (subject).
* Transparent transformation of model exceptions into application-level exceptions/errors
* Pluggable delivery mechanisms (Web, CLI, MessageQueues, Mail, Unit-Tests, ..)  map to the same model
* Command pattern approach (Could allow to keep transactional log of the domain events: Do, Undo, Redo)
* Simplify transaction-management
* Hooks for Validation/Input Filtering
* Avoid testing applications through controllers/frameworks

## Concepts

* Context\Engine is a wrapper to call model commands from in your application.
* Input sources describe what request data is available to the model,
  for example superglobals or console arguments.
* Parameter Converters are rules to convert input/request-data into
  model-request data, for example "2010-01-01" into a datetime instance.
* ExceptionHandler catch every exception from the model layer and allow to turn
  it into an application level exception.

## Example: Calculator

This is a very simple README compatible example for a statistical calculator. It
computes the average, variance and standard deviation of a list of numbers.

    class Calculator
    {
        public function statistics(array $numbers)
        {
            if (count($numbers) > count(array_filter($numbers, 'is_numeric'))) {
                throw new \InvalidArgumentException("Input values are not numeric.");
            }

            $average           = array_sum($numbers) / count($numbers);
            $square            = array_map(array($this, 'square'), $numbers);
            $variance          = (array_sum($square) / count($square)) - $this->square($average);
            $standardDeviation = sqrt($variance);

            return array(
                'numbers'           => $numbers,
                'average'           => $average,
                'variance'          => $variance,
                'standardDeviation' => $standardDeviation,
            );
        }

        public function square($x)
        {
            if ( ! is_numeric($x)) {
                throw new \InvalidArgumentException("Input values are not numeric.");
            }

            return $x * $x;
        }
    }

We want to implement a bunch of example applications on top of this model. During this
experiment we will not change a single line of this model, yet make it usable from very
different types of applications. Lets start with a completly unabstracted PHP/HTTP application:

    <?php
    $calculator = new Calculator();

    if (isset($_GET['numbers'])) {
        $engine = new \Context\Engine();
        $stats   = $engine->execute(array(
            'context' => array($calculator, 'statistics'),
            'params'  => array($_GET['numbers']),
        ));

        $html = <<<HTML
    <strong>Numbers:</strong> %s<br />
    <strong>Average:</strong> %s<br />
    <strong>Variance:</strong> %s<br />
    <strong>Standard Deviation:</strong> %s<br />
    HTML;

        echo sprintf(
            $html,
            $stats['numbers'],
            $stats['average'],
            $stats['variance'],
            $stats['standardDeviation']
        );
    }

The `Context\Engine` just delegates the execution to the given context with the given parameters
in this case, no additional abstraction. We can make use of the first abstraction of Context,
we could try automatic mapping of input parameters into the model request. If the request
contains a variable "numbers" it could be mapped on the $numbers parameter.

    <?php
    $engine = new \Context\Engine();
    $engine->addInput(new \Context\Input\PhpSuperGlobalsInput());

    $stats = $engine->execute(array(
        'context' => array($calculator, 'statistics')
    ));

If you studied the Calculator code you saw that it throws exceptions on invalid input. Context
can handle exceptions from the model layer and transform them into valuable messages for the user:

    <?php
    $engine = new \Context\Engine();
    $engine->addExceptionHandler(function ($e) {
        echo $e->getMessage();
        die();
    });

Now lets move one step further and turn this logic into a command line application.
Parameters are assigned to the 'statistics' function based on the positional arguments
to the script:

    <?php
    // see examples/calculator/argv.php
    $engine = new \Context\Engine();
    $engine->addParamConverter(new \Context\ParamConverter\StringToArrayConverter());
    $engine->addInputSource(new \Context\Input\ArgvInput());

    $stats = $engine->execute(array(
        'context' => array($calculator, 'statistics'),
    ));

    var_dump($stats);

The 'statistics' method only takes one argument, an array. How can we pass the array
on the command-line? The StringToArrayConverter allows us to call this script with:

    php examples/calculator/argv.php "1,2,3,4"

If we want multiple options instead we can use the "GetOptInput" source:

    <?php
    // see examples/calculator/getopt.php
    $engine = new \Context\Engine();
    $engine->addParamConverter(new \Context\ParamConverter\StringToArrayConverter());
    $engine->addInputSource(new \Context\Input\GetOptInput());

    $stats = $engine->execute(array(
        'context' => array($calculator, 'statistics'),
        'shortOptions' => '',
        'longOptions' => array('numbers:'),
    ));

Call this script with:

    php examples/calculator/getopt.php --numbers 1 --numbers 2 --numbers 3 --numbers 4

There are two concepts at work inside the Context Engine, when a request model is created
from application inputs:

1. Input instances are sources for input parameters
   during creation of the request model.
2. Parameter converters look at the method signature of
   your model and convert application input into model
   request input based on rules and priorities.

## Input sources

There are some default input sources shipped with Context:

* PhpSuperGlobalsInput
* ArgvInput
* GetOptInput

Input sources can be much more powerful, by using the request information
of your application framework. Input Sources could be:

* Message Queues (RabbitMQ, ZeroMQ, ...)
* REST API (XML, JSON Data)
* SOAP
* Mail Pipes

The RequestData can be array paramters with key-value pairs, raw data or both.
These RequestData information is then available to the ParamConverters.

## Parameter Converters

There are several parameter converts that ship with Context, however the power of Context
can only be leveraged when you write your own converters, specific to the application
framework you are using.

* StringToArrayConverter - Converters a comma-seperated string into an array.
* DateTimeConverter - Converts a string or an array into a DateTime instance.
* ObjectConverter - Converts an array into an object by mapping keys of the array to constructor argument names, setter or public properties.

There are also generic converter which plugins should override:

* AbstractPersistenceConverter - converters an array or an identifier value into a persistent object from your storage layer.
* ServiceRegistryConverter - Grabs requested services based on type-hint information from a service registry.

We ship with a set of implementations in the "Context\Plugin" namespace:

* EntityConverter using Doctrine2 ObjectManager
* ContainerConverter using Symfony2 DI Container

### ObjectConverter

The object converted needs a special introduction, as it is the powerhouse of Context.

Assume you have simple messaging application that sends e-mails to lists of recipients. One functionality
would be the accepting, validating, and sending of an actual message:

    <?php
    class MessageService
    {
        public function send(Sender $sender, Message $message, RecipientSpecification $spec)
        {
            // code: $sender sends $message to all recipients matching $spec
        }
    }

There are three distinct arguments here, the Sender, the Message and a description of all
recipients. These classes are data-transfer-objects and we can use them for validation and input filtering:

    <?php
    class Message
    {
        public $subject;
        public $body;

        public function __construct($subject, $body)
        {
            if (strlen($subject) > 100) {
                throw new \RuntimeException("Subject is not allowed to be larger than 100");
            }
            $this->subject = strip_tags($subject);
            $this->body = strip_tags($body);
        }
    }

Now the ObjectConverter comes into play, as it allows us to automatically map arrays to these structs.
When invoking the `MessageSerivce#send()` method through Context, the param converter will detect the
argument type hints and will try to convert an input array into that type hinted class.

It is using the following semantics:

- If any constructor argument name matches a name of the array the value is injected there.
- If the class has a setter method named "set$key" it is used to inject the parameter.
- If the class has a public property name after the key the value is injected there.
- If the class implements `ArrayAccess` or `__set`, the value is injected this way.

An example of request arguments to make the MessageService working would be:

    <?php
    $engine = new \Context\Engine();
    $engine->addParamConverter(new \Context\ParamConverter\ObjectConverter());

    $engine->execute(array(
        'context' => array($messageService, 'send'),
        'params' => array(
            'sender'  => array('email' => 'kontakt@beberlei.de'),
            'spec'    => array('list' => 1234),
            'message' => array('subject' => 'Hello World!', 'body' => 'Hello World from body!')
        )
    ));

With the `PhpSuperGlobalsInput` for example you could take the parameter information from `$_POST` automatically,
it gets filtered and validated by the struct objects.

## Plugins

The plugin system acts as an AOP-layer around your model code. The model is the join-point, where additional behavior
can be useful to add around. This additional behavior is called Advice. The following advices are general purpose
behaviors that can be useful in your application:

* Logging
* Error Handling (and in fact the Context Exception Handler is an Advice)
* Transactional Boundary, wrap every call into a transaction of your persistence layer (Doctrine TransactionAdvice)
* Parameter Converters
* Validation/Filtering
* (Replayable) Command Log of every action against your model
* Authorization (Access Control)

### Doctrine Transaction Advice

The Doctrine Transaction Advice wraps all calls of the context engine in a transaction using
`$entityManager->beginTransaction()` and `$entityManager->commit()`. When an exception is thrown
the transaction is rolled back.

At the end of a successful transaction `EntityManager#flush()` is called by the advice.

To activate the advice you have to set 'tx' => true. In a web application you could set
this as a default option based on HEAD/GET or other requests methods automatically.

*Benefit:* With this Advice you can avoid to inject the EntityManager into your model layer.
This allows for a perfect seperation of your business logic and the transactional semantics
of a database.

### Symfony Form Advice

The Symfony Form Advice helps you handle Symfony forms with Context. A typical symfony form
workflow has three important blocks of code:

1. The business logic to execute when an object is updated/created.
2. The failure block when your model is not valid and has to be re-displayed.
3. The success block when model is valid and a redirect+flash messages happen.

In "Context" the first bullet point is part of the model and the other two are part
of the application/UI logic. Generic seperation of these three concerns is not very easy
but possible.

The Symfony Advice allows you to register an alternative context callbacks which is executed
in the event of model validation failure. The 'context' is called only if the
form is valid.

    class CalculactorController
    {
        public function statisticsAction()
        {
            $engine = $this->getContextEngine();
            $result = $engine->execute(array(
                'context' => array($calculator, 'statistics'),
                'type'    => new NumberType(),
                'failure' => array($this, 'statisticsFormAction'),
            ));

            $this->get('flash')->setFlash('notice', 'yay!');
            $this->redirect($this->generateUrl('route'));
        }

        public function statisticsFormAction(Form $form, MyObject $data)
        {
            return array('form' => $form->createView(), 'object' => $data);
        }
    }

This advice is only executed when the Symfony Request is 'POST' and the
content-type is 'application/x-www-form-urlencoded' or 'form/multipart'.

If you use the `InstanceConverter` ParamConverter the Form and Data
of the form get injected using typehints. Otherwise you can use the `form`
and `form_data` variable names to have access to this parameters.

*Benefit:* This advice lets you use Symfony forms and integrate them into
calls against your model layer. You should never use domain objects as
the form models but attach the Request Model (Struct) objects.

### Symfony JMS Serializer Converter

If you are using JMSSerializerBundle you can have Context Engine convert
the raw request body to an object.

For this to work the "Content-Type" header has to be something with xml
or json to set the format and the data has to be in the request body.

*Benefit:* Lets you easily map REST requet bodies to your model parameters.
