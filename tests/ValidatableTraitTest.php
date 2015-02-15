<?php
/**
 * Created by PhpStorm.
 * User: ofca
 * Date: 2015-02-15
 * Time: 00:43
 */

namespace {


    use DC\EloquentValidatable\Exception;
    use DC\EloquentValidatable\ValidatableTrait;
    use Illuminate\Events\Dispatcher;
    use Illuminate\Container\Container;
    use Illuminate\Database\ConnectionResolver;
    use Illuminate\Database\Connectors\ConnectionFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\Facade;
    use Illuminate\Validation\Factory;

    class Foo extends Model {
        use ValidatableTrait;

        public function getValidator()
        {
            return [
                'rules' => [
                    'name'  => 'required'
                ]
            ];
        }
    }

    class ValidatableTraitTest extends PHPUnit_Framework_TestCase
    {
        /**
         * @var \Illuminate\Database\Connection
         */
        protected static $connection;

        /**
         * @var Dispatcher
         */
        protected static $events;

        public static function setUpBeforeClass()
        {
            date_default_timezone_set('Europe/Warsaw');

            $app = new Container();

            $app->bindShared('validator', function($app) {
                return new Factory($app['translator'], $app);
            });

            $app->bindShared('translator', function($app) {
                return new Symfony\Component\Translation\Translator('en');
            });

            // Create database connection
            $factory = new ConnectionFactory($app);
            static::$connection = $factory->make([
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => ''
            ]);

            $resolver = new ConnectionResolver();
            $resolver->addConnection('default', static::$connection);
            $resolver->setDefaultConnection('default');

            Model::setConnectionResolver($resolver);
            Model::setEventDispatcher(static::$events = new Dispatcher());

            static::$events->listen('eloquent.saving*', function($model) {
                if (in_array('DC\EloquentValidatable\ValidatableTrait', class_uses($model))) {
                    return $model->validate();
                }
            });

            Facade::setFacadeApplication($app);
        }

        /**
         * @expectedException \DC\EloquentValidatable\Exception
         */
        public function testException()
        {
            $foo = new Foo;
            $foo->save();
        }

        public function testExceptionErrors()
        {
            $foo = new Foo;

            try {
                $foo->save();
            } catch (Exception $e) {
                $this->assertSame('validation.required', $e->getErrors()->get('name')[0]);
            }
        }
    }

}
