<?php
/**
 * This file is part of cakephp-thumber.
 *
 * cakephp-thumber is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-thumber is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-thumber.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */

namespace Thumber\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Thumber\Utility\ThumbCreator as BaseThumbCreator;

/**
 * Makes public some protected methods/properties from `ThumbCreator`
 */
class ThumbCreator extends BaseThumbCreator
{
    public function getExtension()
    {
        return $this->extension;
    }

    public function getPath()
    {
        return $this->path;
    }
}

/**
 * ThumbCreatorTest class.
 *
 * Some tests use remote files (`remote-file` group tag).
 * To exclude these tests, you can use `phpunit --exclude-group remote-file`.
 */
class ThumbCreatorTest extends TestCase
{
    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Plugin::load('TestPlugin');
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        Plugin::unload('TestPlugin');

        //Deletes all assets
        foreach (glob(Configure::read('Thumbs.target') . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Test for `__construct()` method, passing a no existing file
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File `webroot/img/noExistingFile.gif` not readable
     * @test
     */
    public function testConstructNoExistingFile()
    {
        new ThumbCreator('noExistingFile.gif');
    }

    /**
     * Test for `__construct()` method, passing a no existing file from plugin
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File `Plugin/TestPlugin/webroot/img/noExistingFile.gif` not readable
     * @test
     */
    public function testConstructNoExistingFileFromPlugin()
    {
        new ThumbCreator('TestPlugin.noExistingFile.gif');
    }

    /**
     * Test for `$extension` property
     * @ŧest
     */
    public function testExtension()
    {
        $thumber = new ThumbCreator('400x400.png');
        $this->assertEquals($thumber->getExtension(), 'png');

        $thumber = new ThumbCreator(WWW_ROOT . 'img' . DS . '400x400.png');
        $this->assertEquals($thumber->getExtension(), 'png');

        $thumber = new ThumbCreator('400x400.gif');
        $this->assertEquals($thumber->getExtension(), 'gif');

        $thumber = new ThumbCreator('400x400.jpg');
        $this->assertEquals($thumber->getExtension(), 'jpg');

        $thumber = new ThumbCreator('400x400.jpeg');
        $this->assertEquals($thumber->getExtension(), 'jpeg');

        //From plugin
        $thumber = new ThumbCreator('TestPlugin.400x400.png');
        $this->assertEquals($thumber->getExtension(), 'png');

        //From remote
        $thumber = new ThumbCreator('http://example.com.png');
        $this->assertEquals($thumber->getExtension(), 'png');

        $thumber = new ThumbCreator('http://example.com.png?');
        $this->assertEquals($thumber->getExtension(), 'png');

        $thumber = new ThumbCreator('http://example.com.png?param');
        $this->assertEquals($thumber->getExtension(), 'png');

        $thumber = new ThumbCreator('http://example.com.png?param=value');
        $this->assertEquals($thumber->getExtension(), 'png');
    }

    /**
     * Test for `$path` property
     * @ŧest
     */
    public function testPath()
    {
        $file = WWW_ROOT . 'img' . DS . '400x400.png';

        $thumber = new ThumbCreator('400x400.png');
        $this->assertEquals($thumber->getPath(), $file);

        $thumber = new ThumbCreator($file);
        $this->assertEquals($thumber->getPath(), $file);

        //From plugin
        $file = Plugin::path('TestPlugin') . 'webroot' . DS . 'img' . DS . '400x400.png';

        $thumber = new ThumbCreator('TestPlugin.400x400.png');
        $this->assertEquals($thumber->getPath(), $file);

        $thumber = new ThumbCreator($file);
        $this->assertEquals($thumber->getPath(), $file);

        //From remote
        $file = 'http://example.com.png';

        $thumber = new ThumbCreator($file);
        $this->assertEquals($thumber->getPath(), $file);
    }

    /**
     * Test for `resize()` method
     * @ŧest
     */
    public function testResize()
    {
        $regex = sprintf(
            '/^%sresize_[a-z0-9]+_w200_[a-z0-9]+\.png$/',
            preg_quote(Configure::read('Thumbs.target') . DS, '/')
        );

        $thumb = (new ThumbCreator('400x400.png'))->resize(200);
        $this->assertFileExists($thumb);
        $this->assertRegExp($regex, $thumb);
        $this->assertEquals(array_values(getimagesize($thumb))[0], 200);
        $this->assertEquals(array_values(getimagesize($thumb))[1], 200);
        $this->assertEquals(array_values(getimagesize($thumb))[5], 'image/png');

        $regex = sprintf(
            '/^%sresize_[a-z0-9]+_h200_[a-z0-9]+\.png$/',
            preg_quote(Configure::read('Thumbs.target') . DS, '/')
        );

        $thumb = (new ThumbCreator('400x400.png'))->resize(null, 200);
        $this->assertFileExists($thumb);
        $this->assertRegExp($regex, $thumb);
        $this->assertEquals(array_values(getimagesize($thumb))[0], 200);
        $this->assertEquals(array_values(getimagesize($thumb))[1], 200);
        $this->assertEquals(array_values(getimagesize($thumb))[5], 'image/png');
    }

    /**
     * Test for `resize()` method, using  the `aspectRatio` option
     * @ŧest
     */
    public function testResizeAspectRatio()
    {
        $regex = sprintf(
            '/^%sresize_[a-z0-9]+_w200_h300_[a-z0-9]+\.png$/',
            preg_quote(Configure::read('Thumbs.target') . DS, '/')
        );

        //In this case, the thumbnail will keep the ratio
        $thumb = (new ThumbCreator('400x400.png'))->resize(200, 300, ['aspectRatio' => true]);
        $this->assertFileExists($thumb);
        $this->assertRegExp($regex, $thumb);
        $this->assertEquals(array_values(getimagesize($thumb))[0], 200);
        $this->assertEquals(array_values(getimagesize($thumb))[1], 200);

        //In this case, the thumbnail will not maintain the ratio
        $thumb = (new ThumbCreator('400x400.png'))->resize(200, 300, ['aspectRatio' => false]);
        $this->assertFileExists($thumb);
        $this->assertRegExp($regex, $thumb);
        $this->assertEquals(array_values(getimagesize($thumb))[0], 200);
        $this->assertEquals(array_values(getimagesize($thumb))[1], 300);
    }

    /**
     * Test for `resize()` method, using  the `upsize` option
     * @ŧest
     */
    public function testResizeUpsize()
    {
        $regex = sprintf(
            '/^%sresize_[a-z0-9]+_w450_h450_[a-z0-9]+\.png$/',
            preg_quote(Configure::read('Thumbs.target') . DS, '/')
        );

        //In this case, the thumbnail will keep the original dimensions
        $thumb = (new ThumbCreator('400x400.png'))->resize(450, 450, ['upsize' => true]);
        $this->assertFileExists($thumb);
        $this->assertRegExp($regex, $thumb);
        $this->assertEquals(array_values(getimagesize($thumb))[0], 400);
        $this->assertEquals(array_values(getimagesize($thumb))[1], 400);

        //In this case, the thumbnail will exceed the original size
        $thumb = (new ThumbCreator('400x400.png'))->resize(450, 450, ['upsize' => false]);
        $this->assertFileExists($thumb);
        $this->assertRegExp($regex, $thumb);
        $this->assertEquals(array_values(getimagesize($thumb))[0], 450);
        $this->assertEquals(array_values(getimagesize($thumb))[1], 450);

        $regex = sprintf(
            '/^%sresize_[a-z0-9]+_h450_[a-z0-9]+\.png$/',
            preg_quote(Configure::read('Thumbs.target') . DS, '/')
        );

        //In this case, the thumbnail will exceed the original size
        $thumb = (new ThumbCreator('400x400.png'))->resize(null, 450, ['upsize' => false]);
        $this->assertFileExists($thumb);
        $this->assertRegExp($regex, $thumb);
        $this->assertEquals(array_values(getimagesize($thumb))[0], 450);
        $this->assertEquals(array_values(getimagesize($thumb))[1], 450);
    }

    /**
     * Test for `resize()` method, using  `aspectRatio` and `upsize` options
     * @ŧest
     */
    public function testResizeAspectRatioAndUpsize()
    {
        $regex = sprintf(
            '/^%sresize_[a-z0-9]+_w500_h600_[a-z0-9]+\.png$/',
            preg_quote(Configure::read('Thumbs.target') . DS, '/')
        );

        //In this case, the thumbnail will keep the ratio and the original dimensions
        $thumb = (new ThumbCreator('400x400.png'))->resize(500, 600, [
            'aspectRatio' => true,
            'upsize' => true,
        ]);
        $this->assertFileExists($thumb);
        $this->assertRegExp($regex, $thumb);
        $this->assertEquals(array_values(getimagesize($thumb))[0], 400);
        $this->assertEquals(array_values(getimagesize($thumb))[1], 400);

        //In this case, the thumbnail will not keep the ratio and the original dimensions
        $thumb = (new ThumbCreator('400x400.png'))->resize(500, 600, [
            'aspectRatio' => false,
            'upsize' => false,
        ]);
        $this->assertFileExists($thumb);
        $this->assertRegExp($regex, $thumb);
        $this->assertEquals(array_values(getimagesize($thumb))[0], 500);
        $this->assertEquals(array_values(getimagesize($thumb))[1], 600);

        $regex = sprintf(
            '/^%sresize_[a-z0-9]+_h600_[a-z0-9]+\.png$/',
            preg_quote(Configure::read('Thumbs.target') . DS, '/')
        );

        //In this case, the thumbnail will not keep the ratio and the original dimensions
        $thumb = (new ThumbCreator('400x400.png'))->resize(null, 600, [
            'aspectRatio' => false,
            'upsize' => false,
        ]);
        $this->assertFileExists($thumb);
        $this->assertRegExp($regex, $thumb);
        $this->assertEquals(array_values(getimagesize($thumb))[0], 400);
        $this->assertEquals(array_values(getimagesize($thumb))[1], 600);
    }
}
