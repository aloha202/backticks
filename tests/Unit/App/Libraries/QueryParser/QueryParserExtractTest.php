<?php

namespace App\Libraries\QueryParser;
use App\Libraries\QueryParser;
use Tests\TestCase;
class QueryParserExtractTest extends TestCase
{
    protected $queryParser;
    protected $queryPreparator;
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->queryParser = new QueryParser($this, $this);
        $this->queryPreparator = new QueryPreparator();
    }


    public function testExtractTags() {

        foreach(self::dataExtractTags() as $dataItem) {
            $preparedQuery = $this->queryPreparator->prepareQuery($dataItem[0]);
            $result = array_map(function($tag){
                return $tag->value;
            }, $this->queryParser->extractTags($preparedQuery));
            $result = $this->queryPreparator->replaceBack($result, true);
            $this->assertEquals($dataItem[1], $result);
        }

    }

    public function testExtractConditionals() {

        foreach(self::dataExtractConditionals() as $dataItem) {
            $preparedQuery = $this->queryPreparator->prepareQuery($dataItem[0]);
            $result = array_map(function($conditional){
                return $conditional->value;
            }, $this->queryParser->extractConditionals($preparedQuery));
            $result = $this->queryPreparator->replaceBack($result, true);
            $this->assertEquals($dataItem[1], $result);
        }

    }

    public function testExtractConditionalsAndTags() {

        foreach(self::dataExtractConditionalsAndTags() as $dataItem) {
            $preparedQuery = $this->queryPreparator->prepareQuery($dataItem[0]);
            $result = array_map(function($tag){
                return $tag->value;
            }, $this->queryParser->extractConditionalsAndTags($preparedQuery));
            $result = $this->queryPreparator->replaceBack($result, true);
            $this->assertEquals($dataItem[1], $result);
        }

    }

    public function testExtractTagsAndConditionalsWithTags() {

        foreach(self::dataExtractTagsAndConditionalsWithTags() as $dataItem) {
            $preparedQuery = $this->queryPreparator->prepareQuery($dataItem[0]);
            $result = array_map(function($tag){
                if ($tag->conditional === null) {
                    return $tag->value;
                } else {
                    return join(',', array_map(function ($tag){
                        return $tag->value;
                    }, $tag->conditional->tags));
                }
            }, $this->queryParser->extractConditionalsAndTags($preparedQuery));
            $result = $this->queryPreparator->replaceBack($result, true);
            $this->assertEquals($dataItem[1], $result);
        }

    }

    public static function dataExtractTagsAndConditionalsWithTags() {
        return [
            ['<tag>', ['tag']],
            ["Hello, <tag>, ++IF(1==1){hello}++'", ['tag', '']],
            ["Hello, <burbank|query@wait> ++IF(<tag>==<other>){<tag|other>}++ ok?",
                [
                    'burbank|query@wait',
                    'tag,other,tag|other',
                ]
            ]
        ];
    }

    protected static function dataExtractConditionalsAndTags() {
        return [
            ["<vendor_id> <slugified_title>++IF(<company|toLowerCase>`=`'epix' && <release_year>`>`1980){'EP_'}++", ['vendor_id', 'slugified_title', '#conditional0']],
            ["This is ++IF some text ++ and ++IF another text ++ and ++IF more text ++ and another ++ even more ++ ++<tag> ++ ++IF", ["#conditional0", "#conditional1", "#conditional2", 'tag']],
            ["<name> is the real name of <actors|index:1::@last_name @first_name|toUpperCase>
  and ++IF(<company|toLowerCase>`=`'epix' && <release_year>`>`1980){' '<copyright_line>}++
   and <billing_id|regexSearchReplace:'/PO/':'Billing_ID_'|toUpperCase>==<billing_id|regexCaptureGroup:'/\d{2}$/'>",
                [
                    'name',
                    'actors|index:1::@last_name @first_name|toUpperCase',
                    '#conditional0',
                    "billing_id|regexSearchReplace:'/PO/':'Billing_ID_'|toUpperCase",
                    "billing_id|regexCaptureGroup:'/\d{2}$/'",
                ],
            ],
        ];
    }

    protected static function dataExtractConditionals() {
        return [
            ["<vendor_id> <slugified_title>++IF(<company|toLowerCase>`=`'epix' && <release_year>`>`1980){'EP_'}++", ["(<company|toLowerCase>`=`'epix' && <release_year>`>`1980){'EP_'}"]],
            ["<vendor_id> <slugified_title>++IF(<company|toLowerCase>`=`'epix' && <release_year>`>`1980){' '<copyright_line>}++ some more", ["(<company|toLowerCase>`=`'epix' && <release_year>`>`1980){' '<copyright_line>}"]],
            ["This is ++IF some text ++ and ++IF another text ++ and ++IF more text ++",
                ["some text", "another text", "more text"]],
            ["This is ++IF some text ++ and ++IF another text ++ and ++IF more text ++ and another ++ even more ++ ++ ++ ++IF",
                ["some text", "another text", "more text"]],
            [" Hello ++IF
            (a < b) {1} ELSE {2}
            ++ bye", ['(a < b) {1} ELSE {2}']],
        ];
    }
    protected static function dataExtractTags():array {
        return [
            ['<tag>', ['tag']],
            ['My name is <name>', ['name']],
            ['<name> is the real name of <actors|index:1::@last_name @first_name|toUpperCase>', ['name', 'actors|index:1::@last_name @first_name|toUpperCase']],
            ['- <vendor_id> <slugified_title> ))((', ['vendor_id', 'slugified_title']],
            ["<billing_id|regexSearchReplace:'/PO/':'Billing_ID_'|toUpperCase>==<billing_id|regexCaptureGroup:'/\d{2}$/'>", ["billing_id|regexSearchReplace:'/PO/':'Billing_ID_'|toUpperCase", "billing_id|regexCaptureGroup:'/\d{2}$/'"]],
        ];
    }
}
