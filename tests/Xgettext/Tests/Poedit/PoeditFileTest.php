<?php

namespace Xgettext\Tests\Poedit;

use Xgettext\Tests\TestCase,
    Xgettext\Poedit\PoeditFile,
    Xgettext\Poedit\PoeditString as String;

class PoeditFileTest extends TestCase
{
    public function testFile()
    {
        $file = new PoeditFile('my header', array(new String('key', 'value')));
        $this->assertInstanceOf('\Xgettext\Poedit\PoeditFile', $file);
        $this->assertEquals('my header', $file->getHeaders());
        $file->setHeaders('my new header');
        $this->assertEquals('my new header', $file->getHeaders());
        $this->assertEquals(null, $file->getString('baz'));
    }

    public function testStrings()
    {
        $file = new PoeditFile();
        $file->addString(new String('foo', 'bar', array('comment1')));
        $file->addString(new String('bar', 'baz', array('comment1')));
        $this->assertCount(2, $file->getStrings());
        $this->assertCount(1, $file->getString('foo')->getComments());

        $file->addString(new String('foo', 'bar', array('comment2')));
        $this->assertCount(2, $file->getStrings());
        $this->assertCount(2, $file->getString('foo')->getComments());

        $file->removeString('bar');
        $file->removeString('notfound');
        $this->assertCount(1, $file->getStrings());
        $this->assertTrue($file->hasString('foo'));
        $this->assertFalse($file->hasString('bar'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWrongConstruct()
    {
        $file = new PoeditFile(null, array('foo', 'bar'));
    }

    public function testGetters()
    {
        $file = new PoeditFile();
        $file->addString(new String('foo', 'bar', array('comment1'), array(), array(), array(), true));
        $file->addString(new String('bar', 'baz', array('comment1')));
        $file->getString('bar')->setFuzzy(true);
        $file->addString(new String('qux'));

        $untranslated = $file->getUntranslated();
        $fuzzy = $file->getFuzzy();
        $translated = $file->getTranslated();
        $deprecated = $file->getDeprecated();

        $this->assertCount(1, $translated);
        $this->assertCount(1, $fuzzy);
        $this->assertCount(1, $untranslated);
        $this->assertCount(1, $deprecated);
    }

    public function testStringsConflict()
    {
        $file = new PoeditFile();
        $file->addString(new String('foo', 'bar', array('comment1'), array('extracted1'), array('ref1'), array('flag1'), true));
        $file->addString(new String('foo', 'baz', array('comment2'), array('extracted2'), array('ref2'), array('flag2')));

        $this->assertEquals('baz', $file->getString('foo')->getValue());
        $this->assertEquals(array('comment2'), $file->getString('foo')->getComments());
        $this->assertEquals(array('extracted2'), $file->getString('foo')->getExtracteds());
        $this->assertEquals(array('ref2'), $file->getString('foo')->getReferences());
        $this->assertEquals(array('flag2'), $file->getString('foo')->getFlags());

        $file->addString(new String('foo', 'baz', array('comment3'), array('extracted3'), array('ref3'), array('flag3')));
        $this->assertEquals(array('comment2', 'comment3'), $file->getString('foo')->getComments());
        $this->assertEquals(array('extracted2', 'extracted3'), $file->getString('foo')->getExtracteds());
        $this->assertEquals(array('ref2', 'ref3'), $file->getString('foo')->getReferences());
        $this->assertEquals(array('flag2', 'flag3'), $file->getString('foo')->getFlags());
    }
}
