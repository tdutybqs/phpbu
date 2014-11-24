<?php
namespace phpbu\Backup\Cleaner;

use DirectoryIterator;
use phpbu\App\Result;
use phpbu\Backup\Cleaner;
use phpbu\Backup\Collector;
use phpbu\Backup\Target;
use phpbu\Util\String;
use RuntimeException;
use phpbu\Backup\Collector;

/**
 * Cleanup backup directory.
 *
 * Removes all files older then a given offset.
 *
 * @package    phpbu
 * @subpackage Backup
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  2014 Sebastian Feldmann <sebastian@phpbu.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpbu.de/
 * @since      Class available since Release 1.0.0
 */
class Outdated implements Cleaner
{
    /**
     * Original XML value
     *
     * @var string
     */
    protected $offsetRaw;

    /**
     * Offset in seconds.
     *
     * @var integer
     */
    protected $offsetSeconds;

    /**
     * @see \phpbu\Backup\Cleanup::setup()
     */
    public function setup(array $options)
    {
        if (!isset($options['older'])) {
            throw new RuntimeException('option \'older\' is missing');
        }
        $seconds = String::toTime($options['older']);
        if ($seconds < 1) {
            throw new RuntimeException(sprintf('invalid value for \'older\': %s', $options['older']));
        }
        $this->offsetRaw     = $options['older'];
        $this->offsetSeconds = $seconds;
    }

    /**
     * @see \phpbu\Backup\Cleanup::cleanup()
     */
    public function cleanup(Target $target, Result $result)
    {
        $path    = dirname($target);
        $dItter  = new DirectoryIterator($path);
        $minTime = time() - $this->offsetSeconds;
        $collector = new Collector($target);
        $files     = $collector->getBackupFiles();

        foreach ($files as $file) {
            // last mod date < min date? delete!
            if ($file->getMTime() < $minTime) {
                if (!$file->isWritable()) {
                    throw new RuntimeException(sprintf('can\'t detele file: %s', $file->getPathname()));
                }
                $result->debug(sprintf('delete %s', $file->getPathname()));
                unlink($file->getPathname());
            }
        }
    }
}
