# Context

Thin PHP library to help implement Data-Context-Interaction/
Entity-Boundary-Interceptor Patterns and have a simple way to move logic from
controller to the model layer. It is a "framework for the model".

This library tries to solve problems with todays Web/MVC applications, which
are too focused on the controller, leading to tight coupling and painful
reusability, testing and refactoring experience.

Features:

* Support mapping HTTP-Request (or any input for that matter) onto model-request
* Inject observer that notifies boundary of different model results (subject).
* Simplify transaction-management
* Consistent transformation of model exceptions into exceptions/error handling of application.
* Pluggable delivery mechanisms (Web, CLI, MessageQueues, Unit-Tests, ..)  map to the same model
* Command pattern approach, allowing to keep transactional log of the domain events. (Do, Undo, Redo)
* Hooks for Validation/Input Filtering

## Terminology

### Model

Layer where business logic and access to persistence happens. Can be seperated
into two parts Interactor and Entity.

### Delivery Mechanism

The delivery mechanism is a way input/output and data processing work in your
application: Human/Machine, Asynchroneous/Synchroneus, HTTP/REST/CLI/GUI, Client/Server, and so on.

### Boundary

Seperates Input from model layer by describing how to map delivery mechanism
inputs into a request against your model. The boundary acts an observer to
model (subject) to listen to success and failure states.

### Context (Interaction)

A context is any PHP callable (no-base class?) that handles a use-case. Its execution is wrapped in a boundary.

## Simple Example: Symfony View Resource Request

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
To achieve some way of abstraction the entity manager service + finder has to be hidden behind
an interface + implementation:

    <?php
    class UserRepository implements UserRepositoryInterface
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

The controller could then be rewritten using a command/work pattern:

    <?php

    class UserController extends Controller
    {
        public function viewAction($id)
        {
            $repository = $this->container->get('user_repository'); // our service

            $boundary = new Symfony2Boundary(); // probably needs dependencies
            return $boundary->invoke(array(
                'context' => array($repository, 'find'),
                'success' => function (User $User) {
                    return $this->render('MyApplicationBundle:User:view.html.twig', array(
                        'user' => $user
                    ));
                },
                'exception' => function($e) {
                    throw new HttpNotFoundException();
                }
            ));
        }
    }

Now if this pattern is repetitive in your domain for every view of a repository you can extract the
different controller parts, again gaining reusable code:

    <?php
    abstract class EntityController extends Controller
    {
        public function viewSuccess($entity)
        {
            $template = $this->getTemplateFor($entity);
            return $this->render($template, array('object' => $entity));
        }

        public function onException($e)
        {
            throw new HttpNotFoundException();
        }
    }

    class UserController extends EntityController
    {
        public function viewAction($id)
        {
            $repository = $this->container->get('user_repository');

            $boundary = new Symfony2Boundary();
            return $boundary->invoke(array(
                'context'   => array($repository, 'find'),
                'success'   => array($this, 'viewSuccess'),
                'exception' => array($this, 'onException'),
                'template'  => 'MyApplicationBundle:User:view.html.twig',
            ));
        }
    }

Outlook: You could even get rid of the 'exception' key by defining this globally in the framework
once and only overriding this observer callback for specific use-cases. Depending
on the framework you could even get rid of the controller layer completly for CRUD
operations and directly map from request/routing into a context (works for Symfony2).

## How does Input/Output Mapping work?

Whenever either the context or an observer event is invoked the arguments are resolved
using the Reflection API:

    * Gather possible arguments from options to Boundary invocation or by retrieving request variables.
    * Get all arguments definitions of the method/function.
    * Match variables by type-hints from the list of potential arguments. 
    * Match variables by name from the list of potential arguments.
    * Throw exception if a variable cannot be matched and is required, use default otherwise.
    * Transform value from its boundary representation into the requesed model representation.
    * Invoke method/function with arguments.

There are some special cases:

    * The 'success' closure gets passed the return value of the 'context' when not executed explicitly.

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

## Testing

Context provides a very simple boundary that does require any dependencies. You can use it to execute
Unit-/Functional-Tests for your context objects.
