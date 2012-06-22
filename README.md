# Context

Small PHP library to help you shield your business-rules from the
controller and presentation layers. To achieve this goal the Data-Context-Interaction
and Entity-Boundary-Interceptor patterns are used. Context is a framework "for the model".

This library tries to solve problems with todays Web/MVC applications and their 
focus on the controller. Using MVC frameworks the "documented" way often leads
to tight coupling and painful reusability, testing and refactoring experience.

Context does not interfere in your model. It offers a convenience wrapper around your model
that acts as translation mechanism between presentation layer and model.

## Features

* Support mapping Requests (HTTP, Console, or any input for that matter) on model-request
* Encourage Observers that notify presentation layer of different model results (subject).
* Transparent transformation of model exceptions into application-level exceptions/errors
* Pluggable delivery mechanisms (Web, CLI, MessageQueues, Unit-Tests, ..)  map to the same model
* Command pattern approach (Could allow to keep transactional log of the domain events: Do, Undo, Redo)
* Simplify transaction-management
* Hooks for Validation/Input Filtering
* Seperate testing for controller-patterns from model

## Example: Calculator

This is a very simple README compatible example for a calculator.

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
        $context = new \Context\Engine();
        $stats   = $context->execute(array(
            'context' => array($calculator, 'statistics'),
            'params'  => $_GET['numbers'],
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
    $context = new \Context\Engine();
    $context->setMapper(new \Context\Mapper\PhpSuperGlobalsMapper());

    $stats = $context->execute(array(
        'context' => array($calculator, 'statistics')
    ));

If you studied the Calculator code you saw that it throws exceptions on invalid input. Context
can handle exceptions from the model layer and transform them into valuable messages for the user:

    <?php
    $context = new \Context\Engine();
    $context->setExceptionHandler(function ($e) {
        echo $e->getMessage();
        die();
    });

Now lets move one step further and turn this logic into a command line application.
Parameters are assigned to the 'statistics' function based on the positional arguments
to the script:

    <?php
    $context = new \Context\Engine();
    $context->setMapper(new \Context\Mapper\ArgvMapper());

    $stats   = $context->execute(array(
        'context' => array($calculator, 'statistics'),
    ));

The 'statistics' method only takes one argument, an array. How can we pass the array
on the command-line? Two options seem possible:

1. Comma-seperated list: "1,2,3,4,5,6"
2. Multiple Opt-Arguments with the same name: --numbers 1 --numbers 2

Lets see how this looks in code:

    <?php
    $context = new \Context\Engine();
    $context->addParamConverter(new \Context\ParamConverter\CommaSeperatedListConverter());

Or for the second:

    <?php
    $context = new \Context\Engine();
    $context->setMapper(new \Context\Mapper\GetOptMapper());

    $stats   = $context->execute(array(
        'context' => array($calculator, 'statistics'),
        'shortOptions' => "n:",
        "longOptions" => array("number:"),
    ));

## Terminology

### Model

Layer where business logic and access to persistence happens. Can be sub-divided
into two distinct parts: Interactor and Entity.

### Entity/Data

Data objects which contain application independent business rules.

### Interactor

Use-Case objects that contain application-specific business rules.

### Delivery Mechanism

The delivery mechanism is a way input/output and data processing work in your
application: Human/Machine, Asynchroneous/Synchroneus, HTTP/REST/CLI/GUI, Client/Server, and so on.

### Boundary

Seperates Input from model layer by describing how to map delivery mechanism
inputs into a request against your model. The boundary acts an observer to
model (subject) to listen to success and failure states.

### Context (Interaction)

A context is any class that handles a use-case and therefore very similar to the interactor concept.

### Entity-Boundary-Interactor

Pattern that describes how to seperate delivery mechanisms from model through boundaries.
Interactors contain the behavior of the model, entity represent the static data model.

### Data-Context-Interaction

Pattern that helps solve the mental-model mismatch between static data and behavior.
The Interactor from EBI-Pattern is a context that manages a use-case and the data
objects are "casted into" roles containing behavior. This can be done through aggregation.

## Command Pattern

The context library implements a command-pattern. That means that we build
command object or methods that implement full use-cases. For all use-cases that
manipulate data this means you have to wrap them in methods.

In cases where you only need to display data from the persistence layer and no
logic happens wrapping the code in an additional layer is not necessary.

## Simple Example: Symfony View Request (No Context needed)

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

The only thing missing here is the Symfony `NotFoundException`. However you can override
the error handler in Symfony and handle all domain exceptions there.

## How does Input/Output Mapping work?

Whenever either the context or an observer event is invoked the arguments are resolved
using the Reflection API:

    * Gather possible arguments from options 'variables' or by retrieving request variables.
    * Get all arguments definitions of the method/function.
    * Match variables by type-hints from the list of potential arguments. 
    * Match variables by name from the list of potential arguments.
    * Throw exception if a variable cannot be matched and is required, use default otherwise.
    * Transform value from its boundary representation into the requesed model representation.
    * Invoke method/function with arguments.

There are some special cases:

    * The 'success' closure gets passed the return value of the 'context' when not executed explicitly.
    * ContextObserver is an interface that get injected when type-hinted.

Possible argument transformation:

    * String/Integer into DateTime
    * Scalar/Array Primary Key into Object
    * Scalar/Array into Value Object
    * Array into New Object
    * ...

Open Question how to configure if an object is only mapped from an integer, not created/updated from an array?

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
                    $entityManager = $this->get('doctrine.orm.default_entity_manager');
                    $entityManager->persist($user);

                    $mailer = $this->get('mailer');
                    $mailer->send(...);

                    $session = $this->get('session');
                    k$session->getFlashBag()->add('success', 'You have registered!');

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
            // do much more work here.
            $this->entityManager->persist($candidate);

            return $candidate;
        }
    }

The form handling of the controller contains generic-reusable code for all forms in your application:

    <?php

    class SymfonyFormBoundary extends Boundary
    {
        protected function execute(Context $context, array $options)
        {
            $form = $this->createForm($options['type']);
            $failureHandler = $options['failure'];

            if ($options['request']->getMethod() != 'POST') {
                return $failureHandler($form);
            }

            $form->bindRequest($options['request']);

            if ( ! $form->isValid()) {
                return $failureHandler($form);
            }

            $args = $this->resolveContextArguments($context, $options['request']);

            try {
                $ret = call_user_func_array(array($context, 'execute'), $args);
                $successHandler = $options['success'];

                return $successHandler($ret);
            } catch(\Exception $e) {
                $exceptionHandler = $options['exception'];
                return $exceptionHandler($e, $form);
            }
        }
    }

The transactional code for a Doctrine EntityManager:

    <?php
    class DoctrineOrmTransaction implements ContextTransaction
    {
        public function beginTransaction()
        {
            $this->entityManager->beginTransaction();
        }

        public function commit()
        {
            $this->entityManager->flush();
            $this->entityManager->commit();
        }

        public function rollback()
        {
            $this->entityManager->rollBack();
        }
    }

The controller is then without any abstraction over the boundary:

    <?php
    class RegistrationController
    {
        public function registerAction()
        {
            $boundary = new SymfonyFormBoundary(); // deps missing
            return $boundary->invoke(array(
                'context' => new RegisterUserContext(),
                'type'    => new UserType(),
                'request' => $this->getRequest(),
                'tx'      => $this->container->get('context.tx.doctrine_orm'),
                'success' => function($user, $controller) {
                    $mailer = $controller->get('mailer');
                    $mailer->send(...);

                    $session = $controller->get('session');
                    $session->getFlashBag()->add('success', 'You have registered!');

                    return $controller->redirect($controller->generateUrl('someroute'));
                },
                'failure' => function($form) {
                    return $this->render(
                        'Bundle:Registration:register.html.twig',
                        array('form' => $form->createView())
                    );
                }
            ));
        }
    }

With an abstraction layer over the boundary and re-use of behavior this could simplify:

    <?php
    class RegistrationController
    {
        public function registerAction()
        {
            $boundary = new SymfonyFormBoundary(); // deps missing
            return $boundary->invoke(array(
                // 'tx', 'request' injected automatically in SF2 context
                'context' => new RegisterUserContext(),
                'type' => new UserType(),
                'success' => array($this, 'sendMailFlashRedirectAction'),
                'failure' => array($this, 'renderFailureResponseAction'),
            ));
        }
        
        public function renderFailureResponseAction($form)
        {
            return $this->render(
                'Bundle:Registration:register.html.twig',
                array('form' => $form->createView())
            );
        }

        public function sendMailFlashRedirectAction($user)
        {
            $mailer = $this->get('mailer');
            $mailer->send(...);

            $session = $this->get('session');
            $session->getFlashBag()->add('success', 'You have registered!');

            return $this->redirect($this->generateUrl('someroute'));
        }
    }

## Observer

If you typehint for `Context\ContextObserver` interface in your model you get the observer injected:

    <?php
    interface ContextObserver
    {
        function notify($event, array $variables);
    }

    class RegisterUserContext
    {
        public function register(ContextObserver $observer)
        {
            //...
            $observer->notify('some_event', array('foo' => 'bar'));
            //...
        }
    }

By default Context create an observer that will invoke closures from the options
array passed to the `Boundary::invoke`` method. You can override the observer
by setting 'observer' key at boundary invocation.

## Testing

Context provides a very simple boundary that does require any dependencies. You can use it to execute
Unit-/Functional-Tests for your context objects.

    <?php

    class RegisterUserContextTest extends PHPUnit_Framework_TestCase
    {
        public function testRegister()
        {
            $boundary = new SimpleBoundary();
            $value = $boundary->invoke(array(
                'variables' => array('user' => new User()),
            ));

            // assertions on $value
        }
    }
