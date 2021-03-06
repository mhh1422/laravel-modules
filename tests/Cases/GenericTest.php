<?php

/**
 * Created by PhpStorm.
 * User: Muhannad Shelleh <muhannad.shelleh@live.com>
 * Date: 3/16/17
 * Time: 12:44 PM
 */

namespace ItvisionSy\Laravel\Modules\Tests\Cases;

use Config;
use ErrorException;
use Exception;
use ItvisionSy\Laravel\Modules\Modules;
use ItvisionSy\Laravel\Modules\StoreHandlers\DummyStoreHandler;
use ItvisionSy\Laravel\Modules\Tests\ExtendedModules;
use ItvisionSy\Laravel\Modules\Tests\LaravelModulesTestCase;
use stdClass;

class GenericTest extends LaravelModulesTestCase {

    public function testFailStaticAccess() {
        $this->expectException(ErrorException::class);
        Modules::invalidMethod();
    }

    public function testFailedPublicAccess() {
        $this->expectException(ErrorException::class);
        $modules = new Modules();
        $modules->invalidMethod();
    }

    public function testSuccPublicAccess() {
        $this->assertNull(Modules::getStoredValue("some_not_defined_value", null));
    }

    public function testModulesServiceProvider() {
        $this->artisan('make:module', ["id" => "Test", "name" => "Test Module"]);
        $this->loadModuleFiles("Test");
        $this->refreshApplication();
        $this->assertEquals(\App\Modules\Test\Module::class, get_class(Modules::get('Test')));
    }

    public function testInvalidModulesServiceProvider() {
        $this->expectException(Exception::class);
        $this->refreshApplication();
        Modules::get('InvalidTest');
    }

    public function testCallableHandler() {
        $this->refreshApplication();
        Config::set('modules.store_handler', function () {
            return DummyStoreHandler::make();
        });
        ExtendedModules::resetStoreHandler();
        $this->assertEquals(DummyStoreHandler::class, get_class(Modules::getStoreHandler()));
    }

    public function testEmptyHandler() {
        $this->refreshApplication();
        Config::set('modules.store_handler', false);
        ExtendedModules::resetStoreHandler();
        $this->assertEquals(DummyStoreHandler::class, get_class(Modules::getStoreHandler()));
    }

    public function testInvalidHandlerSet() {
        $this->expectException(ErrorException::class);
        $this->refreshApplication();
        Config::set('modules.store_handler', new stdClass());
        ExtendedModules::resetStoreHandler();
        Modules::getStoreHandler();
    }

    public function testDeletedModuleFiles() {

        //Deleted
        $this->artisan('make:module', ["id" => "Test2", "name" => "Test Module"]);
        $this->rm(rtrim(Modules::modulesDirectory(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "Test2" . DIRECTORY_SEPARATOR . "Module.php");
        $this->loadModuleFiles("Test2");
        $this->refreshApplication();
        Modules::refreshModules();
        try {
            Modules::get('Test2');
        } catch (Exception $e) {
            $this->assertEquals("Module not found: Test2", $e->getMessage());
        }

        //No class defined
        $this->artisan('make:module', ["id" => "Test3", "name" => "Test Module"]);
        file_put_contents(rtrim(Modules::modulesDirectory(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "Test3" . DIRECTORY_SEPARATOR . "Module.php", "<?php ");
        $this->loadModuleFiles('Test3');
        Modules::refreshModules();
        try {
            Modules::get('Test3');
        } catch (Exception $e) {
            $this->assertEquals("Module not found: Test3", $e->getMessage());
        }

        //incorrect module class inheritence
        $this->expectException(Exception::class);
        $this->artisan('make:module', ["id" => "Test4", "name" => "Test Module"]);
        file_put_contents(rtrim(Modules::modulesDirectory(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "Test4" . DIRECTORY_SEPARATOR . "Module.php", "<?php namespace App\\Modules\\Test4; class Module { }");
        $this->loadModuleFiles('Test4');
        Modules::refreshModules();
        Modules::get('Test4');
    }

}
