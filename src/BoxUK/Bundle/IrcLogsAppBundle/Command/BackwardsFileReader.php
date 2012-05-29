<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Command;

/**
 * Reads a file, backwards, line by line
 */
class BackwardsFileReader
{

    /**
     * Filename
     *
     * @var string
     */
    protected $filename = null;

    /**
     * Filehandle
     *
     * @var resource
     */
    protected $filehandle = null;

    /**
     * @var int
     */
    private $pos = -2;

    /**
     * @var bool
     */
    private $beginning = false;

    /**
     * Constructor
     *
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->openFile($filename);
    }

    /**
     * Open file
     *
     * @param string $filename
     *
     * @throws \RuntimeException
     * @return BackwardsFileReader
     */
    public function openFile($filename)
    {
        $this->closeFile();
        if (!is_file($filename)) {
            throw new \RuntimeException("Invalid file ($filename).");
        }
        if (!is_readable($filename)) {
            throw new \RuntimeException("File not readable ($filename).");
        }
        $this->filename = $filename;
        if (false === $this->filehandle = @fopen($filename, 'r')) {
            throw new \RuntimeException("Failed to open file ($filename).");
        }

        // jump to the end of the file
        fseek($this->filehandle, -1, SEEK_END);
        return $this;
    }

    /**
     * Close file
     *
     * Closes any open files.
     *
     * @return BackwardsFileReader
     */
    public function closeFile()
    {
        if (is_resource($this->filehandle)) {
            fclose($this->filehandle);
        }
        return $this;
    }

    /**
     * Read line
     *
     * @throws \RuntimeException
     * @return string Returns a string if a line is read, false on failure or no line
     */
    public function readLine()
    {
        if (!is_resource($this->filehandle)) {
            throw new \RuntimeException('Can\'t perform a readLine, no file open.');
        }

        if ($this->beginning) {
            return false;
        }

        do {

            $char = '';
            while ($char != "\n") {
                if (fseek($this->filehandle, $this->pos, SEEK_END) == -1) {
                    $this->beginning = true;
                    rewind($this->filehandle);
                    break;
                }
                $char = fgetc($this->filehandle);
                $this->pos--;
            }

            $line = @fgets($this->filehandle);

            // Jump back to where we started reading
            fseek($this->filehandle, -mb_strlen($line, '8bit'), SEEK_CUR);

            if ($line === false) {
                throw new \RuntimeException('Can\'t read line.');
            } else {
                return $line;
            }


        } while (true);
    }

}