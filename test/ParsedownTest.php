<?php

require 'SampleExtensions.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParsedownTest extends TestCase
{
    protected ?TestParsedown $Parsedown = null;

    public static function data(): array
    {
        $dirs = [__DIR__ . '/data/'];
        $data = [];

        foreach ($dirs as $dir) {
            $Folder = new DirectoryIterator($dir);

            foreach ($Folder as $File) {
                /** @var $File DirectoryIterator */

                if (!$File->isFile()) {
                    continue;
                }

                $filename = $File->getFilename();

                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if ($extension !== 'md') {
                    continue;
                }

                $basename = $File->getBasename('.md');

                if (file_exists($dir . $basename . '.html')) {
                    $data [] = [$basename, $dir];
                }
            }
        }

        return $data;
    }

    public function testLateStaticBinding()
    {
        $parsedown = Parsedown::instance();
        $this->assertInstanceOf('Parsedown', $parsedown);

        // After instance is already called on Parsedown
        // subsequent calls with the same arguments return the same instance
        $sameParsedown = TestParsedown::instance();
        $this->assertInstanceOf('Parsedown', $sameParsedown);
        $this->assertSame($parsedown, $sameParsedown);

        $testParsedown = TestParsedown::instance('test late static binding');
        $this->assertInstanceOf('TestParsedown', $testParsedown);

        $sameInstanceAgain = TestParsedown::instance('test late static binding');
        $this->assertSame($testParsedown, $sameInstanceAgain);
    }

    function testRawHtml()
    {
        $markdown           = "```php\nfoobar\n```";
        $expectedMarkup     = '<pre><code class="language-php"><p>foobar</p></code></pre>';
        $expectedSafeMarkup = '<pre><code class="language-php">&lt;p&gt;foobar&lt;/p&gt;</code></pre>';

        $unsafeExtension = new UnsafeExtension;
        $actualMarkup    = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);

        $unsafeExtension->setSafeMode(true);
        $actualSafeMarkup = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedSafeMarkup, $actualSafeMarkup);
    }

    function testTrustDelegatedRawHtml()
    {
        $markdown           = "```php\nfoobar\n```";
        $expectedMarkup     = '<pre><code class="language-php"><p>foobar</p></code></pre>';
        $expectedSafeMarkup = $expectedMarkup;

        $unsafeExtension = new TrustDelegatedExtension;
        $actualMarkup    = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);

        $unsafeExtension->setSafeMode(true);
        $actualSafeMarkup = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedSafeMarkup, $actualSafeMarkup);
    }

    /**
     *
     * @param $test
     * @param $dir
     */
    #[DataProvider('data')]
    public function test_($test, $dir)
    {
        $markdown = file_get_contents($dir . $test . '.md');

        $expectedMarkup = file_get_contents($dir . $test . '.html');

        $expectedMarkup = str_replace("\r\n", "\n", $expectedMarkup);
        $expectedMarkup = str_replace("\r", "\n", $expectedMarkup);

        $this->Parsedown->setSafeMode(str_starts_with($test, 'xss'));
        $this->Parsedown->setStrictMode(str_starts_with($test, 'strict'));

        $actualMarkup = $this->Parsedown->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);
    }

    public function test_no_markup()
    {
        $markdownWithHtml = <<<MARKDOWN_WITH_MARKUP
<div>_content_</div>

sparse:

<div>
<div class="inner">
_content_
</div>
</div>

paragraph

<style type="text/css">
    p {
        color: red;
    }
</style>

comment

<!-- html comment -->
MARKDOWN_WITH_MARKUP;

        $expectedHtml = <<<EXPECTED_HTML
<p>&lt;div&gt;<em>content</em>&lt;/div&gt;</p>
<p>sparse:</p>
<p>&lt;div&gt;
&lt;div class="inner"&gt;
<em>content</em>
&lt;/div&gt;
&lt;/div&gt;</p>
<p>paragraph</p>
<p>&lt;style type="text/css"&gt;
p {
color: red;
}
&lt;/style&gt;</p>
<p>comment</p>
<p>&lt;!-- html comment --&gt;</p>
EXPECTED_HTML;

        $parsedownWithNoMarkup = new TestParsedown();
        $parsedownWithNoMarkup->setMarkupEscaped(true);
        $this->assertEquals($expectedHtml, $parsedownWithNoMarkup->text($markdownWithHtml));
    }

    /**
     * @return TestParsedown
     */
    protected function initParsedown(): TestParsedown
    {
        return new TestParsedown();
    }

    protected function setUp(): void
    {
        $this->Parsedown = $this->initParsedown();
    }
}
