<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test Parsedown against the CommonMark spec
 *
 * @link http://commonmark.org/ CommonMark
 */
class CommonMarkTestStrict extends TestCase
{
    public const string SPEC_URL = 'https://raw.githubusercontent.com/jgm/CommonMark/master/spec.txt';

    protected ?TestParsedown $parsedown = null;

    /**
     * @return array
     */
    public static function data(): array
    {
        $spec = file_get_contents(self::SPEC_URL);
        if ($spec === false) {
            return [];
        }

        $spec = str_replace("\r\n", "\n", $spec);
        $spec = strstr($spec, '<!-- END TESTS -->', true);

        $matches = [];
        preg_match_all('/^`{32} example\n((?s).*?)\n\.\n(?:|((?s).*?)\n)`{32}$|^#{1,6} *(.*?)$/m', $spec, $matches, PREG_SET_ORDER);

        $data           = [];
        $currentId      = 0;
        $currentSection = '';
        foreach ($matches as $match) {
            if (isset($match[3])) {
                $currentSection = $match[3];
            } else {
                $currentId++;
                $markdown     = str_replace('→', "\t", $match[1]);
                $expectedHtml = isset($match[2]) ? str_replace(['→'], ["\t"], $match[2]) : '';

                $data[$currentId] = [
                    'id'           => $currentId,
                    'section'      => $currentSection,
                    'markdown'     => $markdown,
                    'expectedHtml' => $expectedHtml,
                ];
            }
        }

        return $data;
    }

    /**
     * @param $id
     * @param $section
     * @param $markdown
     * @param $expectedHtml
     */
    #[DataProvider('data')]
    public function testExample($id, $section, $markdown, $expectedHtml)
    {
        $actualHtml = $this->parsedown->text($markdown);
        $this->assertEquals($expectedHtml, $actualHtml);
    }

    protected function setUp(): void
    {
        $this->parsedown = new TestParsedown();
        $this->parsedown->setUrlsLinked(false);
    }
}
