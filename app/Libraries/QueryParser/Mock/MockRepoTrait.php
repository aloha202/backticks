<?php

namespace App\Libraries\QueryParser\Mock;

use App\Libraries\QueryParser\Exception\MethodRepositoryException;
use App\Libraries\QueryParser\Exception\TagRepositoryException;
use Illuminate\Database\Eloquent\Collection;

trait MockRepoTrait
{

    public function getTag($name) {
        switch ($name) {
            case 'year20':
                return 1965;
            case 'year21':
                return 2017;
            case 'false':
                return false;
            case 'emptystring':
                return '';
            case 'number10':
                return 10;
            case 'string':
                return 'Long string';

            case 'object':
                $obj = new \stdClass();
                $obj->name = 'John';
                $obj->fullname = 'John Doe';
                $obj->age = 18;
                $obj->country = 'usa';
                $obj->email = 'johndoe@gmail.com';
                return $obj;
            case 'object2':
                $obj = new \stdClass();
                $obj->emptystring = '';
                $obj->at = '@';

                return $obj;

            case 'com1':
                $obj1 = new \stdClass();
                $obj1->name = 'John';
                $obj1->year = 1998;
                $obj2 = new \stdClass();
                $obj2->name = 'Mike';
                $obj2->year = 2001;

                return new Collection([$obj1, $obj2]);

            case 'badcom':
                $obj1 = new \stdClass();
                $obj1->name = 'hello';

                return new Collection([$obj1, 'string']);

            case 'array':
                return ['First', 'Second', 'Third'];

            case 'tags':
                return array_map(function($item){
                    return $item[0];
                }, $this->_getInfo('tags'));

            case 'functions':
                return array_map(function($item){
                    return $item[0];
                }, $this->_getInfo('functions'));
        }

        if (in_array(strtolower($name), [
            123,
            'tag',
            'happy',
            'test',
            'one',
            'tricky',
            'upper',
            'a',
            'now',
            'two',
            'mary',
            'useless',
            'any',
            'anytag',
            'some',
            'riddle',
            'john',
            'hello',
        ])) {
            return $name;
        }

        throw new TagRepositoryException("Unknown tag '{$name}'");
    }

    public function callFunction($func, $params)
    {
        switch ($func) {
            case 'methodName':
                return $func;
            case 'equalsto':
                return $params[0] == $params[1] ? 'yes' : 'no';
            case 'isEmptyString':
                return $params[0] === '' ? 'yes' : 'no';
            case 'numparams':
                return count($params) - 1;
            case 'concat':
                return $params[0] . $params[1];
            case 'upper':
                return strtoupper($params[0]);
            case 'arg1':
                return $params[1];
            case 'plus':
                return $params[0] + $params[1];
            case 'join':
                return join($params[1], $params[0]);
            case 'index':
                return $params[0][$params[1]];
            case 'indexOrFalse':
                return isset($params[0][$params[1]]) ? $params[0][$params[1]] : false;

        }

        if (in_array($func, [
            'method',
            'this',
            'methodAsStringLiteral',
            'any',
        ])) {
            return $params[0];
        }

        throw new MethodRepositoryException("Unknown function '{$func}'");
    }

    public function _getInfo($what) {
        switch ($what) {
            case "functions":
                return [
                    ['methodName', "Returns string 'methodName'", 'string'],
                    ['equalsto', "Returns 'yes' of parameter is equal to context and 'no' otherwise", 'string'],
                    ['isEmptyString', "Checks whether parameter is an empty string, return 'yes' or 'no'", 'string'],
                    ['numparams', "Returns number of passed parameters", 'int'],
                    ['concat', "Concats context and parameter", 'string'],
                    ['upper', "Makes context upper case", 'string'],
                    ['arg1', "Returns parameter", 'any'],
                    ['plus', "Adds parameter to context", 'number'],
                    ['join', "Joins elements of array using parameter", 'string'],
                    ['index', "Returns element of a collection or array", 'any'],
                    ['indexOrFalse', "Returns element of a collection or array, if isset, false otherwise", 'any'],
                    ['method', "Returns context"],
                    ['this', "Returns context"],
                    ['methodAsStringLiteral', "Returns context"],
                    ['any', "Returns context"],
                ];
            case "tags":
                return [
                    ["functions", "List of available functions", "array"],
                    ["tags", "List of available tags", "array"],
                    ['year20', "1965", 'int'],
                    ['year21', "2017", 'int'],
                    ['false', 'false', 'boolean'],
                    ['emptystring', "''", 'string'],
                    ['number10', "10", 'int'],
                    ['string', "'Long string'", 'string'],
                    ['object', "Object with the following values:
                name = 'John'
                fullname = 'John Doe'
                age = 18
                country = 'usa'
                email = 'johndoe@gmail.com'
                    ", 'object'],
                    ['object2', "Object with the following values:
                emptystring = ''
                at = '@'
                    "],
                    ['com1', "Collection of object with the following values:
                0:name = 'John'
                0:year = 1998
                1:name = 'Mike'
                1:year = 2001
                    ", 'com'],
                    ['badcom', "Malformed collection with and object and a string:
                    obj1:name = 'hello'
                    string = 'string'", 'com'],
                    ['array', "Array: ['First', 'Second', 'Third']", 'array'],
                    [123],
                    ['tag'],
                    ['happy'],
                    ['test'],
                    ['one'],
                    ['tricky'],
                    ['upper'],
                    ['a'],
                    ['now'],
                    ['two'],
                    ['mary'],
                    ['useless'],
                    ['any'],
                    ['anytag'],
                    ['some'],
                    ['riddle'],
                    ['john'],
                    ['hello'],

                ];

        }
        return [];
    }
}
