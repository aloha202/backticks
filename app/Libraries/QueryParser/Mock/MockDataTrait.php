<?php

namespace App\Libraries\QueryParser\Mock;

use App\Libraries\QueryParser\Exception\ExecutionErrorException;
use App\Libraries\QueryParser\Exception\MethodRepositoryException;
use App\Libraries\QueryParser\Exception\SyntaxErrorException;
use App\Libraries\QueryParser\Exception\TagRepositoryException;
use App\Libraries\QueryParser\Exception\UndefinedVariableException;
use App\Libraries\QueryParser\Processor\Exception\ConditionalProcessorException;

trait MockDataTrait
{
    public static function mockExamples(): array
    {
        return [
            ['<123>', '123'],
            ['<tag>', 'tag'],
            ['<happy>', 'happy'],
            ['<test> <tag>', 'test tag'],
            ['Hello <test> <tag>', 'Hello test tag'],
            ["'Hello' <test>", 'Hello test'],
            ["A '<' B", 'A < B'],
            ["'<tag>' means <TAG>", '<tag> means TAG'],
            ["Here's a tricky <one>", "Here's a tricky one"],
            ["Here'\''s a <tricky> fellow", "Here's a tricky fellow"],
            ["<tag|method>", 'tag'],
            ["<upper|upper>", "UPPER"],
            ["<test|arg1:1>", 1],
            ["Hello, <a|arg1:'world'>", 'Hello, world'],
            ["OK, <now | this>", 'OK, now'],
            ["This <one|upper> is <two|arg1:bigger than> 'that'", "This ONE is bigger than that"],
            ["'10 + 10 is' <number10|plus:10>", "10 + 10 is 20"],
            ["<object::@name>", 'John'],
            ["John to upper case is <object::@name| upper>", 'John to upper case is JOHN'],
            ["<object::@name, aged @age>", 'John, aged 18'],
            ["<object::@name, aged @age' ' | any>", 'John, aged 18 '],
            [
                "<object::@name, aged @age from @country|upper> and <Mary | concat : ' Poppins'>",
                'JOHN, AGED 18 FROM USA and Mary Poppins',
            ],
            ["<test|arg1 : '<'   >", '<'],
            ["'' <useless|numparams:1:2:3:4:5|plus:10>", ' 15'],
            ["''' '", ' '],
            ["''''''", ''],
            ["<emptystring|isEmptyString>", 'yes'],
            ["<any|isEmptyString>", 'no'],
            ["<any   |   arg1   :    ''  |  isEmptyString  >", 'yes'],
            ["<anytag|arg1:'<tag>'>", '<tag>'],
            ["<object::@fullname '<'@email'>'>", 'John Doe <johndoe@gmail.com>'],
            ["<object::'hello'|concat:', world'>", 'hello, world'],
            [
                "<object::@fullname '<'@email'>'> == <object::@fullname> '<'<object::@email>'>'",
                'John Doe <johndoe@gmail.com> == John Doe <johndoe@gmail.com>',
            ],
            ["'::'", '::'],
            ["<object2::@emptystring|equalsto:''>", 'yes'],
            ["<any|arg1:'@at'>", '@at'],
            ["<some|arg1:'|'|equalsto:'|'>", 'yes'],
            ["Hello 'guys': <com1::@name, born @year|join:' and '>", "Hello guys: John, born 1998 and Mike, born 2001"],
            ["<any|arg1 : '\'' >", "'"],
            ["Hello <any|arg1 : '\''| concat : John | concat : '\''>", "Hello 'John'"],
            ["Bad collection of model, but the first element is OK: <badcom|index:0::@name>", "Bad collection of model, but the first element is OK: hello"],
            ["Solve the riddle: <riddle|arg1:'1'|concat:'+'|concat:'1'|concat:'='|concat:'?'>", "Solve the riddle: 1+1=?"],
            ["'<com1|index:1::@name>'=<com1|index:1::@name>", "<com1|index:1::@name>=Mike"],
            ["'<array|index:0|upper>'=<array|index:0|upper>", "<array|index:0|upper>=FIRST"],
            [
                "Hello <John|upper>, \n we wouldn'\''t mind if you <any|arg1:'gave us'|concat : ' your telephone'> '<number>'",
                "Hello JOHN, \n we wouldn't mind if you gave us your telephone <number>",
            ],
            ["<tag|'methodAsStringLiteral'>", 'tag'],
            ["<tag|'methodName'>", 'methodName'],
            ["<tag|'arg1': 1 >", 1],
            ["<tag|`if`:`then`:'exists':`else`:'no exists'>", 'exists'],
            ["<false | `if` : `then` : 'True' : `else` : 'False'>", 'False'],
            ["<emptystring|`if` : `=` : '' : `then` : emptystring : `else` : ''>", 'emptystring'],
            ["<false | `if` : `then` : 'false'>", ''],
            ["<any | arg1 : '\'' | `if` : `=` : '' : `then` : 'empty' : `elseif` : `=` : '\'' : `then` : 'quote' | upper>", 'QUOTE'],
            ["<com1 | indexOrFalse : 10 | `if` : `then` : exists >", ''],
            ["<any | `if` : `and` : '' : `then` : yes : `else` : no | upper>", 'NO'],
            ["<year20 | plus : 10 | `if` : `>=` : 1970 : `and` : `<` : 1980 : `then` : 'seventies' :
                                            `elseif` : `>=` : 1980 : `and` : `<` : 1990 : `then` : 'eighties' :
                                            `elseif` : `>=` : 1990 : `and` : `<` : 2000 : `then` : 'nineties' :
                                            `elseif` : `>=` : 2000 : `then` : 'new millenium' :
                                            `else` : 'sixties or earlier' | upper>", 'SEVENTIES'],
            ["<year20 | plus : 10 | `if` : `>=` : 1970 : `and` : `<` : 1980 : `then` : 'seventies' :
                                            `elseif` : `>=` : 1980 : `and` : `<` : 1990 : `then` : 'eighties' :
                                            `elseif` : `>=` : 1990 : `and` : `<` : 2000 : `then` : 'nineties' :
                                            `elseif` : `>=` : 2000 : `then` : 'new millenium' :
                                            `else` : 'sixties or earlier' | upper> and <year20 | plus : 20 | `if` :
                                            `>=` : 1970 : `and` : `<` : 1980 : `then` : 'seventies' :
                                            `elseif` : `>=` : 1980 : `and` : `<` : 1990 : `then` : 'eighties' :
                                            `elseif` : `>=` : 1990 : `and` : `<` : 2000 : `then` : 'nineties' :
                                            `elseif` : `>=` : 2000 : `then` : 'new millenium' :
                                            `else` : 'sixties or earlier' | upper>", 'SEVENTIES and EIGHTIES'],
            ["<year20 | plus : 10 | `if` : `>=` : 1970 : `and` : `<` : 1980 : `then` : 'seventies' :
                                            `elseif` : `>=` : 1980 : `and` : `<` : 1990 : `then` : 'eighties' :
                                            `elseif` : `>=` : 1990 : `and` : `<` : 2000 : `then` : 'nineties' :
                                            `elseif` : `>=` : 2000 : `then` : 'new millenium' :
                                            `else` : 'sixties or earlier' | upper> and <year20 | plus : 20 | `if` :
                                            `>=` : 1970 : `and` : `<` : 1980 : `then` : 'seventies' :
                                            `elseif` : `>=` : 1980 : `and` : `<` : 1990 : `then` : 'eighties' :
                                            `elseif` : `>=` : 1990 : `and` : `<` : 2000 : `then` : 'nineties' :
                                            `elseif` : `>=` : 2000 : `then` : 'new millenium' :
                                            `else` : 'sixties or earlier' | upper> or <year21 | `if` :
                                            `>=` : 1970 : `and` : `<` : 1980 : `then` : 'seventies' :
                                            `elseif` : `>=` : 1980 : `and` : `<` : 1990 : `then` : 'eighties' :
                                            `elseif` : `>=` : 1990 : `and` : `<` : 2000 : `then` : 'nineties' :
                                            `elseif` : `>=` : 2000 : `then` : 'new millenium' :
                                            `else` : 'sixties or earlier' | upper>", 'SEVENTIES and EIGHTIES or NEW MILLENIUM'],
            ["<any | `if` : `or` : 1 : `then` : '123'>", "123"],
            ["<any | `if` : `and` : '' : `then` : '123'>", ""],
            ["<false | `IF` : `OR` : '' : `OR` : 0 : `OR` : 1 : `THEN` : 'hello'> <false | `iF` : `Or` : 0 : `tHen` : 'echo' : `ElSe` : else>", "hello else"],
            ["<any | arg1: '`else`' | `if` : `<>` : '`else`' : `then` : 'not `else`' : `else` : '`else`'>", "`else`"],
            ["<com1 | index : 0 ::@name| `if` : `=` : 'John' : `then` : 'Hello \'john\'' : `ELSE` : Hello Mike>", "Hello 'john'"],
            ["<com1 | index : 1 ::@year| `if` : `<` : 2000 : `OR` : `!=` : 2001 : `then` : 20 century : `ELSE` : Millenial | upper>:<com1 | index : 1 :: ' '@name, @year>", "MILLENIAL: Mike, 2001"],
            //conditionals
            ["++IF(1){'1'}ELSE{'2'}++", '1'],
            ["'Hello', ++IF(<year20> `>` 2000) {'21st'} ELSE {'20th'}++ century", "Hello, 20th century"],
            ["Well, ++IF(<object::@age|plus : 10> `>` 30) {<object::@name>} ELSEIF (<object::@age|plus : 10> `>` 20) {<object::@fullname>} ELSE {N/A}++ '' ", 'Well, John Doe '],
            ["If ++IF(<emptystring>`==`'' `and` 1){'correct'}ELSE{'incorrect'}++", 'If correct'],
            ["++IF(<any | arg1: '\''> `==` ' '){'space'}ELSEIF(<any | arg1: '\''> `==` '\''){'single quote'}++ and ++IF(<year21> `>` 2000){21st century}++", "single quote and 21st century"],
            ["
            ", ''],

            ["
                <any | `if` : `==` : 'any' : `then` : OK : `else` : NO>++IF
                (<any> `==` any){OK} else {NO}
                ++
            ", "OKOK"],
            [" Hello ++IF
                        (<any> `<>` <any>)
                        {<any> not equal <any>}
                        ELSE
                        {<any> is equal to <any>}
                        ++ and ++IF
                        ('++' `==` '++')
                        {<any | arg1 : '++' > is OK}
                        ELSE
                        {'SORRY'}
                        ++
            ", "Hello any is equal to any and ++ is OK"],
            ["

                This <year21 | `if`:  `>` : 2020 : `then`: i : `else` : wa | concat: s> ++IF
                        (<object:: @name> `==` Mike)
                        {'Mikey\'s'}
                        ELSEIF
                        (<object:: @name> `==` <any | arg1: John>)
                        {'Johnny\'s'}
                        ELSE
                        {'other person\'s'}
                        ++ suitcase

            ", "This was Johnny's suitcase"],

            //Variables
            ["<year21 | arg1 : @this >", 2017],
            ["<tag | concat : @this | upper>", "TAGTAG"],
            ["<number10 | `if` : @context : `>` : 10 : `then` : Bigger : `else` : No>", 'No'],
            [" <tag | concat : @this | `if` : @context : `==` : tagtrag : `then` : 'Bad result' : `else` : Very good | upper>", 'VERY GOOD'],
            ["
                <year20 | `if` : @this : `>` : 1900 : `and` : @this : `<` : 1950 : `then` : First half : `else` : Second half>
            ", "Second half"],
            ["
                <year20 | `if` : @this : `>` : 1900 : `and` : @this : `<` : 1950 : `then` : First half : `else` : Second half>
            ", "Second half"],
            ["
                <year20 | `if` : @this : `>` : 2000 : `or` : @this : `<` : 1900 : `then` : Not 20th : `else` : 20th>
            ", "20th"],

            ["
                <number10 | plus: 10 | `if` : @context : `==` : 10 : `or` : @context : `==` : 12 : `or` : @context : `<` : 15
                : `then` : 'Less than 15'
                : `elseif` : @context : `=` : 16 : `OR` : @context : `<` : 19
                : `then` : Less than 19
                : `elseif` : @context : `<=` : 20
                : `then` : 'It\'s 20'
                : `else` : 'More than 20'
                > and <tag | concat : '' | concat : @this | `if` : @this : `=` : tagtag : `then` : correct! : `else`: incorrect... >
            ", "It's 20 and correct!"],



        ];
    }

    public static function mockExceptions()
    {
        return [
            ["<string::@property>", ExecutionErrorException::class],
            ["Hello <com1::@name|join> , ", ExecutionErrorException::class], // join, not enough arguments
            ["<any|join:''>", ExecutionErrorException::class], // join, first argument must be an array
            ["<bad-tag-name>", [SyntaxErrorException::class, 'bad-tag-name']],
            ["This is a <b@dT@gN@me>", [SyntaxErrorException::class, "'b@dT@gN@me'"]],
            ["<bad|method_n@me>", [SyntaxErrorException::class, "'method_n@me'"]],
            ["<bad||method_name_empty_string>", [SyntaxErrorException::class, "''"]],
            ["< >", [SyntaxErrorException::class, "''"]],
            ["<''>", [SyntaxErrorException::class, "''"]],
            ["<' '>", [SyntaxErrorException::class, "' '"]],
            ["<invalid|parameter:>", [SyntaxErrorException::class, "'parameter:'"]],
            ["<invalid|parameter : : >", [SyntaxErrorException::class, "'parameter : : '"]],
            [" This tag contains a bad collection <badcom::@name>", ExecutionErrorException::class],
            [" Calling 'index' function on a scalar value <number10|index:1>", ExecutionErrorException::class],
            ["<<>",  [SyntaxErrorException::class, "'<'"]],
            ["<<>>",  [SyntaxErrorException::class, "'<'"]],
            ["<'invalid'>",  [SyntaxErrorException::class, "'invalid'"]],
            ["<''>", [SyntaxErrorException::class, "''"]],
            ["<>", [SyntaxErrorException::class, "''"]],
            ["<>>", [SyntaxErrorException::class, "''"]],
            ["<any|`invalidSystemMethod`>", [SyntaxErrorException::class, "`invalidSystemMethod`"]],
            ["<hello|method : `else`>", [ExecutionErrorException::class, "'method'"]],
            ["<unknowntag>", [TagRepositoryException::class , "'unknowntag'"]],
            ["<tag | unknown>", [MethodRepositoryException::class, "'unknown'"]],
            ["<tag | concat : @undefined >", [UndefinedVariableException::class, "'@undefined'"]],
        ];
    }

    public static function mockConditionalExceptions()
    {
        return [
            ["<any|`if` : `then`>   ", ConditionalProcessorException::class],
            ["<any | `if` : `then` : `then`>", ConditionalProcessorException::class],
            ["<any | `if` : 12 : `else`>", ConditionalProcessorException::class],
            ["<any | `if` : `or` : `and`>", ConditionalProcessorException::class],
        ];
    }

    public static function getMoreExamples() {
        return [
            ["<functions|join:' , '>"],
            ["<tags|join:' , '>"],
        ];
    }
}
