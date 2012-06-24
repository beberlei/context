# Context

Small PHP library to help you shield your business-rules from the
controller and presentation layers. To achieve this goal the Data-Context-Interaction
and Entity-Boundary-Interceptor patterns are used. Context is a framework "for the model".

This library was born while trying to solve problems with todays Web/MVC applications and their
focus on the controller. Using MVC frameworks the "documented" way often leads
to tight coupling and painful reusability, testing and refactoring experience.

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
        public function square($x)
        {
            if ( ! is_numeric($x)) {
                throw new \InvalidArgumentException("Input values are not numeric.");
            }

            return $x * $x;
        }

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
    $engine = new \Context\Engine();
    $engine->addInput(new \Context\Input\ArgvInput());

    $stats   = $engine->execute(array(
        'context' => array($calculator, 'statistics'),
    ));

The 'statistics' method only takes one argument, an array. How can we pass the array
on the command-line? Two options seem possible:

1. Comma-seperated list: "1,2,3,4,5,6"
2. Multiple Opt-Arguments with the same name: --numbers 1 --numbers 2

Lets see how this looks in code:

    <?php
    $engine = new \Context\Engine();
    $engine->addParamConverter(new \Context\ParamConverter\CommaSeperatedListConverter());

Or for the second:

    <?php
    $engine = new \Context\Engine();
    $engine->addInput(new \Context\Input\GetOptInput());

    $stats   = $engine->execute(array(
        'context' => array($calculator, 'statistics'),
        'shortOptions' => "n:",
        "longOptions" => array("number:"),
    ));

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
* SessionInput
* ArgvInput
* GetoptInput

Input sources can be much more powerful, by using the request information
of your application framework. Input Sources could be:

* Message Queues (RabbitMQ, ZeroMQ, ...)
* REST API (XML, JSON Data)
* SOAP
* Mail Pipes

## Parameter Converters

There are several parameter converts that ship with Context, however the power of Context
can only be leveraged when you write your own converters, specific to the application
framework you are using.

* CommaSeperatedListConverter - Converters a comma-seperated string into an array.
* DateTimeConverter - Converts a string or an array into a DateTime instance.
* DateIntervalConverter - Converts a string or an array into a DateInterval instance.
* ObjectConverter - Converts an array into an object by mapping keys of the array to constructor argument names, setter or public properties.
* EventArgsConverter - Converts a request into an "EventArgs" argument to the model, containing source and data of the event.
* ServiceConverter - Grabs requested services based on type-hint information from a service registry.

There are also a generic converter related to persistence:

* PersistentObjectConverter - converters an array or an identifier value into a persistent object from your storage layer.

We ship with a set of implementations in the "Context\Plugin" namespace:

* DoctrinePersistentObjectConverter
* SymfonyUserObjectConverter

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

Now With the `PhpSuperGlobalsInput` you could take the parameter information from `$_POST` automatically.

## Plugins

The plugin system acts as an AOP-layer around your model code. The model is the join-point, where additional behavior
can be useful to add around. This additional behavior is called Advice. The following advices are general purpose
behaviors that are very useful:

* Logging
* Error Handling (and in fact the Context Exception Handler is an Advice)
* Transactional Boundary, wrap every call into a transaction of your persistence layer
* Parameter Converters
* Validation/Filtering
* (Replayable) Command Log of every action against your model
* Authorization (Access Control)

## Read-Only Example: Symfony View Request (No Context needed)

There are cased when you don't need context in your controllers, even if you use
it excessively. Whenever there is no advice and no parameter-conversion necessary
then you can just use the actual service object. This applies to many read-only
requests.

In this example a domain object is fetched using a service from a locator and then
rendered as HTML page. Error handling is implemented in terms of the framework.

    <?php

    class UserController extends Controller
    {
        public function viewAction($id)
        {
            $em = $this->container->get('doctrine.orm.default_entity_manager');
            $user = $em->find('MyApplication\Entity\User', $id);

            if ( ! $user) {
                throw $this->createNotFoundException();
            }

            return $this->render('MyApplicationBundle:User:view.html.twig', array(
                'user' => $user
            ));
        }
    }

In this example there is absolutely no abstraction between model, persistence and controller.
To achieve some way of abstraction the Doctrine Entity Manager has to be hidden behind
behind a persistent-ignorant interface and a Doctrine implementation:

    <?php
    interface UserRepositoryInterface
    {
        /**
         * @throws UserNotFoundException
         * @return User
         */
        function find($id);
    }

    class DoctrineUserRepository implements UserRepositoryInterface
    {
        private $em; // constructor omitted

        public function find($id)
        {
            $user = $this->em->find('MyApplication\Entity\User', $id);

            if ( ! $user) {
                throw new UserNotFoundException($id);
            }

            return $user;
        }
    }

The controller could then be rewritten to this simple bit:

    <?php

    class UserController extends Controller
    {
        public function viewAction($id)
        {
            $repository = $this->container->get('user_repository'); // our service

            return $this->render('MyApplicationBundle:User:view.html.twig', array(
                'user' => $repository->find($id)
            ));
        }
    }

The only thing missing here is the conversion of UserNotFoundException into
Symfony `NotFoundException`. So we actually need a very leightweight invocation
through Context:

    <?php

    class MySymfonyExceptionHandler implements ExceptionHandler
    {
        public function catch(Exception $e)
        {
            if ($e instanceof UserNotFoundException) {
                throw NotFoundHttpException;
            }
        }
    }

    class UserController extends Controller
    {
        public function viewAction($id)
        {
            $repository = $this->container->get('user_repository'); // our service

            return $this->render('MyApplicationBundle:User:view.html.twig', array(
                'user' => $this->executeContext(array(
                    'context' => array($repository, 'find'),
                    array($id)
                 ))
            ));
        }

        // reusable parts in your framework
        public function executeContext(array $params)
        {
            return $this->getContextEngine()->execute($params);
        }

        public function getContextEngine()
        {
            $engine = new \Context\Engine;
            $engine->addExceptionHandler(new MySymfonyExceptionHandler());
            return $engine;
        }
    }

## Complex Example: Symfony Form

Lets formulate the default "handle a form" in Symfony2 into "context"

Here the "Symfony approved" way, an example from the documentation:

    <?php

    class RegistrationController extends Controller
    {
        public function registerAction()
        {
            $user = new User();
            $form = $this->createForm(new UserType(), $user);
            $request = $this->getRequest();

            if ($request->getMethod() == 'POST') {
                $form->bindRequest($request);

                if ($form->isValid()) {
                    // do much more work here regarding registration.

                    $entityManager = $this->get('doctrine.orm.default_entity_manager');
                    $entityManager->persist($user);
                    $entityManager->flush();

                    $mailer = $this->get('mailer');
                    $mailer->send(...);

                    $session = $this->get('session');
                    $session->getFlashBag()->add('success', 'You have registered!');

                    return $this->redirect($this->generateUrl('someroute'));
                }
            }

            return $this->render(
                'Bundle:Registration:register.html.twig',
                array('form' => $form->createView())
            );
        }
    }

Model and Controller are so tightly coupled here its hard to take them apart. The use-case is validating
and creating a new user. So a method minus the controller/view/transactional clutter would look like:

    <?php

    class RegisterUserContext
    {
        public function execute(User $candidate)
        {
            // do much more work here regarding registration.
            $this->entityManager->persist($candidate);

            return $candidate;
        }
    }

The form handling of the controller contains generic-reusable code for all forms in your application.
There is no need to write this kind of code more than once. Lets implement an advice, that converts
a form type into its data object and injects it into the parameters.

    <?php
    class SymfonyFormAdvice implements Advice
    {
        public function around(ContextInvocation $invocation);
        {
            $options = $invocation->getOptions();
            $form    = $this->formFactory->createForm($options['type']);

            if ($options['request']->getMethod() !== 'POST') {
                $failureHandler = $options['failure'];
                return $failureHandler($form);
            }

            $form->bindRequest($options['request']);

            if ( ! $form->isValid()) {
                return $failureHandler($form);
            }

            $options['params'][$form->getName()] = $form->getData();

            $data = $innvocation->invoke();
            $successCallback = $options['success'];
            return $successCallback($data);
        }
    }

The transactional code for a Doctrine EntityManager can be wrapped
into a plugin that surrounds the whole command execution.

The controller then becomes an invocating of the context, using
closures as event:

    <?php
    class RegistrationController
    {
        public function registerActionSuccess($user)
        {
            $mailer = $controller->get('mailer');
            $mailer->send(...);

            $session = $controller->get('session');
            $session->getFlashBag()->add('success', 'You have registered!');

            return $this->redirect($controller->generateUrl('someroute'));
        }

        public function registerActionFailure($form)
        {
            return $this->render(
                'Bundle:Registration:register.html.twig',
                array('form' => $form->createView())
            );
        }

        public function registerAction()
        {
            return $this->executeContext(array(
                'context' => array(new RegisterUserContext(), 'execute'),
                'type'    => new UserType(),
                'success' => array($this, 'registerSuccess'),
                'failure' => array($this, 'registerActionFailure'),
            ));
        }
    }

