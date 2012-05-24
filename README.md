# Context

Thin PHP library to help implement Data-Context-Interaction/Entity-Boundary-Interceptor Patterns and have a simple way to move logic from controller to the model layer.
It is a framework for the model layer.

This library tries to solve problems with todays Web/MVC applications, which are too focused on the controller, leading to tight coupling and painful testing experience.

Requirements:

* Solve mapping HTTP-Request (or any input for that matter) onto model-request
* Inject observer that notifies boundary of different model results (subject).
* Allow for Validation/Input Filtering
* Simplify transactions around work
* Transform model exceptions into exceptions/error handling of MVC.
* Allow different boundaries to be implemented (Web, CLI, MessageQueues, Unit-Tests, ..) and map to the same model.
* Command pattern approach, allowing to keep transactional log of the domain events. (Do, Undo, Redo)

## A Context

A context is any PHP callable that handles a use-case. It is executed from the context-engine and
wrapped into transactions, request-to-model mapping and much more.

## Simple Example: Symfony View Resource Request

In this example a resource is accessed that corresponds to one object in the domain layer.

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
an interface:

    <?php
    class UserRepository implements UserRepositoryInterface
    {
        private $em;
        // constructor omitted
        public function find($id)
        {
            $user = $this->em->find('MyApplication\Entity\User', $id);

            if (!$user) {
                throw new EntityNotFoundException('MyApplication\Entity\User', $id);
            }
            
            return $user;
        }
    }

The controller could then be rewritten using a command/work pattern using some anonymous functions:

    <?php

    class UserController extends Controller
    {
        public function viewAction($id)
        {
            $repository = $this->container->get('user_repository');

            return $this->work(array(
                'context' =>  function ($id) use ($repository) {
                    return $repository->find($id);
                },
                'success' => function (User $User) {
                    return $this->render('MyApplicationBundle:User:view.html.twig', array(
                        'user' => $user
                    ));
                },
                'failure' => function() {
                    throw new HttpNotFoundException();
                }
            ));
        }

        protected function work(array $options)
        {
            try {

            } catch(\Exception $e) {

            }
        }
    }

Now if this pattern is repetitive in your domain for every view of a repository you can extract the
different controller parts, again gaining reusable code:

    <?php
    abstract class EntityController extends Controller
    {
        public function viewSuccess($template, $entity, $name)
        {
            return $this->render($template, array($name => $entity));
        }

        public function viewFailure()
        {
            throw new HttpNotFoundException();
        }
    }

    class UserController extends EntityController
    {
        public function viewAction($id)
        {
            $repository = $this->container->get('user_repository');

            return $this->work(array(
                'context' =>  function ($id) use ($repository) {
                    return $repository->find($id);
                },
                'success' => array($this, 'viewSuccess'),
                'failure' => array($this, 'viewFailure'),
            ));
        }
    }


## Complex Example: Symfony Form 

Lets formulate the default "handle a form" in Symfony2 into "context"

Here the "Symfony approved" way, example from the documentation:

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

The form handling of the controller contains generic-reusable code:

    <?php

    class SymfonyFormBoundary extends Boundary
    {
        protected function execute(Context $context, array $options)
        {
            $form = $this->createForm($options['type'], $options['data']);

            if ($options['request']->getMethod() != 'POST') {
                $failure = $options['failure'];
                return $failure($form);
            }

            $form->bindRequest($options['request']);

            if ( ! $form->isValid()) {
                $failure = $options['failure'];
                return $failure($form);
            }

            $args = $this->getContextArguments($context, $options['request'], $options['data']);

            try {
                $ret = call_user_func_array(array($context, 'execute'), $args);
                $success = $options['success'];

                return $success($ret);
            } catch(\Exception $e) {
                return $this->handleException($e, $context, $options);
            }
        }
    }

The transactional code for a Doctrine EntityManager:

    <?php
    class DoctrineOrmTransaction extends ContextTransaction
    {
        public function execute($boundary, $context, $options)
        {
            $this->entityManager->beginTransaction();
            try {
                $boundary->execute($context, $options);
            } catch(\Exception $e) {

            }
        }
    }

The controller is then without any abstraction over the boundary:

    <?php
    class RegistrationController
    {
        public function registerAction()
        {
            $engine = $this->container->get('context.engine');
            $engine->setBoundary(new SymfonyFormBoundary());
            $engine->work(array(
                'context' => new RegisterUserContext(),
                'type' => new UserType(),
                'data' => new User(),
                'request' => $this->getRequest(),
                'success' => function($user) {
                    $mailer = $this->get('mailer');
                    $mailer->send(...);

                    $session = $this->get('session');
                    $session->getFlashBag()->add('success', 'You have registered!');

                    return $this->redirect($this->generateUrl('someroute'));
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
            $this->work(array(
                'context' => new RegisterUserContext(),
                'type' => new UserType(),
                'data' => new User(),
                'success' => array($this, 'sendMailFlashRedirectAction'),
                // Or even since we know the context
                // 'Bundle:RegistrationController:sendMailFlashRedirectAction'
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
