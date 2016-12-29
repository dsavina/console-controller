<?php
namespace Gema\Controllers;
use Mouf\Console\ConsoleApplication;
use Mouf\Mvc\Splash\Annotations\Get;
use Mouf\Mvc\Splash\Annotations\Post;
use Mouf\Mvc\Splash\Annotations\URL;
use Mouf\Security\Logged;
use Mouf\Security\Right;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\TextResponse;

/**
 * Class ConsoleController
 * @package Gema\Controllers
 */
class ConsoleController
{
    /**
     * @var ConsoleApplication
     */
    private $console;

    /**
     * @param ConsoleApplication $console
     */
    public function setConsole(ConsoleApplication $console)
    {
        $this->console = $console;
    }

    /**
     * @URL("/console/v2")
     * @Get()
     * @return HtmlResponse
     */
    public function v2()
    {
        /** @var Command[] $commands */
        $commands = $this->console->all();
        $formattedCommands = [];
        foreach ($commands as $command) {
            $formattedCommands[] = $this->magicFormat($command);
        }

        $jsonCommands = json_encode($formattedCommands);
        $scope = '$scope';
        $http = '$http';
        $value = '$value';


        // -------------------- HTML BLOCK -------------------- //
        $html = <<<HTML
<!doctype html>
<html>
    <head>
        <title>Console</title>
        <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.6/angular.min.js"></script>
        <script>
var COMMANDS = $jsonCommands;
function FormController($scope, $http) {
    $scope.init = function() {
        $scope.commands = COMMANDS;

        var name = window.location.hash.substr(1);
        $scope.cmd = name;
        $scope.selectCommand(name);
        $scope.args = "";
        $scope.input = "";
        $scope.output = {
            status: "ok",
            message: ""
        };
    };
    
    $scope.findCommand = function(name) {
        return $scope.commands.find(function(e){return e.name == name;});
    };
    
    $scope.selectCommand = function() {
        $scope.currentCommand = $scope.findCommand($scope.cmd)
        if ($scope.currentCommand)
            window.location.hash = '#' + $scope.cmd;
    };
    
    $scope.execute = function() {
        $scope.clearOutput();
        var commandLine = $scope.cmd + " " + $scope.args;
        var data = {
            commandLine: commandLine
        }
        $http.post("/console/ajax", data)
        .then(function(response) {
            $scope.input = commandLine;
            $scope.output.status = "ok";
            $scope.output.message = response.data;
        })
        .catch(function (response) {
            $scope.input = commandLine;
            $scope.output.status = "ko";
            $scope.output.message = response.data;
        });
    };
    
    $scope.autocomplete = function(str) {
        var words = str.split(" ");
        var commands = $scope.commands.filter(function(e){return e.name.indexOf(name) == 0;});
    };
    
    $scope.clearOutput = function() {
        $scope.input = "";
        $scope.output = {
            status: "ok",
            message: ""
        };
    };
    
    $scope.init();
};

angular.module('console', []).controller('FormController', ['$scope', '$http', FormController]);

        </script>
        <style>
.ok {
    color: grey;
}
.ko {
    color: red;
}
.mono {
    font-family: monospace;
}
select {
    text-align: right;
}
        </style>

    </head>
    <body class="mono">
        <div ng-app="console">
            <div ng-controller="FormController">
                <form>
                    <input class="mono" list="command-names" ng-model="cmd" data-ng-change="selectCommand()">
                    <datalist id="command-names" class="mono">
                        <option ng-repeat="command in commands" value="{{command.name}}"></option>
                    </datalist>
                    <input style="width: 512px;" class="mono" type="text" ng-model="args">
                    <input type="submit" ng-click="execute()" value="run">
                </form>
                <code ng-if="currentCommand">
                    <p>
                        <b>Description:</b> <i>{{currentCommand.description}}</i>
                    </p>
                    <p>
                        <b>Synopsis:</b> {{currentCommand.synopsis}}<br/>
                    </p>
                    <p ng-if="currentCommand.definition.arguments.length">
                        <b>Arguments:</b><br/>
                        <span ng-repeat="argument in currentCommand.definition.arguments">
                            &nbsp;&nbsp;{{argument.name}}: <i>{{argument.description}}</i><br/>
                        </span>
                    </p>
                    <p ng-if="currentCommand.definition.options.length">
                        <b>Options:</b><br/>
                        <span ng-repeat="option in currentCommand.definition.options">
                            &nbsp;&nbsp;{{option.name}}: <i>{{option.description}}</i><br/>
                        </span>
                    </p>
                    <div style="
                         margin: 32px 64px;
                         padding: 16px;
                         border-style: solid;
                        "
                        ng-if="input">
                        <pre>> <b>{{input}}</b></pre>
                        <pre ng-if="output.message" ng-class="output.status">{{output.message}}</pre>
                    </div>
                </code>
            </div>
        </div>

    </body>
</html>
HTML;

        return new HtmlResponse($html);
    }







    /**
     * @URL("/console")
     * @Get()
     * @return HtmlResponse
     */
    public function index(ServerRequestInterface $request)
    {
        /** @var Command[] $commands */
        $commands = $this->console->all();
        $formattedCommands = [];
        foreach ($commands as $command) {
            $formattedCommands[] = $this->magicFormat($command);
        }

        $jsonCommands = json_encode($formattedCommands);
        $scope = '$scope';
        $http = '$http';
        $value = '$value';


        // -------------------- HTML BLOCK -------------------- //
        $html = <<<HTML
<!doctype html>
<html>
    <head>
        <title>Console</title>
        <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.6/angular.min.js"></script>
        <script>
var COMMANDS = $jsonCommands;
function FormController($scope, $http) {
    $scope.init = function() {
        $scope.commands = COMMANDS;

        var name = window.location.hash.substr(1);
        $scope.cmd = name;
        $scope.selectCommand(name);
        $scope.args = "";
        $scope.input = "";
        $scope.output = {
            status: "ok",
            message: ""
        };
    }
    
    
    $scope.findCommand = function(name) {
        return $scope.commands.find(function(e){return e.name == name;});
    };
    
    $scope.selectCommand = function() {
        $scope.currentCommand = $scope.findCommand($scope.cmd)
        if ($scope.currentCommand)
            window.location.hash = '#' + $scope.cmd;
    };
    
    $scope.execute = function() {
        $scope.clearOutput();
        var commandLine = $scope.cmd + " " + $scope.args;
        var data = {
            commandLine: commandLine
        }
        $http.post("/console/ajax", data)
        .then(function(response) {
            $scope.input = commandLine;
            $scope.output.status = "ok";
            $scope.output.message = response.data;
        })
        .catch(function (response) {
            $scope.input = commandLine;
            $scope.output.status = "ko";
            $scope.output.message = response.data;
        });
    }
    
    $scope.clearOutput = function() {
        $scope.input = "";
        $scope.output = {
            status: "ok",
            message: ""
        };
    }
    
    $scope.init();
};

angular.module('console', []).controller('FormController', ['$scope', '$http', FormController]);

        </script>
        <style>
.ok {
    color: grey;
}
.ko {
    color: red;
}
.mono {
    font-family: monospace;
}
select {
    text-align: right;
}
        </style>

    </head>
    <body class="mono">
        <div ng-app="console">
            <div ng-controller="FormController">
                <form>
                    <input class="mono" list="command-names" ng-model="cmd" data-ng-change="selectCommand()">
                    <datalist id="command-names" class="mono">
                        <option ng-repeat="command in commands" value="{{command.name}}"></option>
                    </datalist>
                    <input style="width: 512px;" class="mono" type="text" ng-model="args">
                    <input type="submit" ng-click="execute()" value="run">
                </form>
                <code ng-if="currentCommand">
                    <p>
                        <b>Description:</b> <i>{{currentCommand.description}}</i>
                    </p>
                    <p>
                        <b>Synopsis:</b> {{currentCommand.synopsis}}<br/>
                    </p>
                    <p ng-if="currentCommand.definition.arguments.length">
                        <b>Arguments:</b><br/>
                        <span ng-repeat="argument in currentCommand.definition.arguments">
                            &nbsp;&nbsp;{{argument.name}}: <i>{{argument.description}}</i><br/>
                        </span>
                    </p>
                    <p ng-if="currentCommand.definition.options.length">
                        <b>Options:</b><br/>
                        <span ng-repeat="option in currentCommand.definition.options">
                            &nbsp;&nbsp;{{option.name}}: <i>{{option.description}}</i><br/>
                        </span>
                    </p>
                    <div style="
                         margin: 32px 64px;
                         padding: 16px;
                         border-style: solid;
                        "
                        ng-if="input">
                        <pre>> <b>{{input}}</b></pre>
                        <pre ng-if="output.message" ng-class="output.status">{{output.message}}</pre>
                    </div>
                </code>
            </div>
        </div>

    </body>
</html>
HTML;

        return new HtmlResponse($html);
    }

    /**
     * @URL("/console/ajax")
     * @Post()
     * @return TextResponse
     */
    public function execute(ServerRequestInterface $request)
    {
        $commandLine = $request->getParsedBody()["commandLine"];
        try {
            $input = new StringInput($commandLine);
            $output = new BufferedOutput();
            $this->console->doRun($input, $output);
        } catch (\Exception $e) {
            $text = "status: " . $e->getCode() . "\n"
                . "error: " . $e->getMessage();
            return new TextResponse($text, 500);
        }

        return new TextResponse($output->fetch(), 200);
    }

    private function magicFormat($object)
    {
        if ($object instanceof Command) {
            return [
                "name" => $object->getName(),
                "description" => $object->getDescription(),
                "help" => $object->getHelp(),
                "definition" => $this->magicFormat($object->getDefinition()),
                "aliases" => $object->getAliases(),
                "usages" => $object->getUsages(),
                "synopsis" => $object->getSynopsis(),
            ];
        }
        if ($object instanceof InputDefinition){
            return [
                "synopsis" => $object->getSynopsis(),
                "arguments" => array_values(array_map([$this, "magicFormat"], $object->getArguments())),
                "options" => array_values(array_map([$this, "magicFormat"], $object->getOptions())),
            ];
        }
        if ($object instanceof InputArgument) {
            return [
                "name" => $object->getName(),
                "description" => $object->getDescription(),
                "default" => $object->getDefault(),
                "required" => $object->isRequired(),
            ];
        }
        if ($object instanceof InputOption) {
            return [
                "name" => $object->getName(),
                "shortcut" => $object->getShortcut(),
                "description" => $object->getDescription(),
                "default" => $object->getDefault(),
                "required" => $object->isValueRequired(),
                "accept" => $object->acceptValue(),
            ];
        }
        return $object;
    }
}

