<?php

namespace Xgettext\Parser;

use Xgettext\Poedit\PoeditString,
    Xgettext\Poedit\PoeditPluralString;

abstract class AbstractParser
{
    protected $file;
    protected $keywords;
    protected $strings;

    public function __construct($file, array $keywords = array('_'))
    {
        $this->file = $file;
        $this->keywords = $this->handleKeywords($keywords);
        $this->strings = array();
    }

    // make keword list and argument positions
    private function handleKeywords($keywords)
    {
        $kwds = array();

        foreach ($keywords as $keyword) {
            if (false !== ($pos = strpos($keyword, ':'))) {
                preg_match_all('`([\d]+)`', $keyword, $matches);

                $kwds[substr($keyword, 0, $pos)] = $matches[0];
                continue;
           }

           $kwds[$keyword] = array(1);
        }

        return $kwds;
    }

    public function parse($string = null)
    {
        $line_count = 0;
        $handle = fopen($this->file, "r");

        // foreach file line by line
        while ($handle && !feof($handle)) {
            $line = fgets($handle);
            $comment = $this->file . ':' . ++$line_count;

            // catch everything looking like keyword(<arguments>)
            preg_match_all($this->getFuncRegex(), $line, $callMatches); // get all that is inside function brackets (arguments)

            // nothing found in the parsed line
            if (!isset($callMatches[1][0])) {
                continue;
            }

            $keyword = $callMatches[1][0];

            // foreach every call match to analyze arguments, they must be strings
            foreach ($callMatches[2] as $arguments) {
                preg_match_all($this->getArgsRegex(), $arguments, $matches);

                // false alert, not valid arguments inside
                if (!isset($matches[2][0])) {
                    continue;
                }

                // detect if string delimiter is ' or " to properly un escape
                $delimiter = $matches[1][0];

                // first argument is msgid
                $msgid = str_replace("\\$delimiter", $delimiter, $matches[2][$this->keywords[$keyword][0] - 1]);

                // if we did not have found already this string, create it
                if (!in_array($msgid, array_keys($this->strings))) {
                    // we have a plural form case
                    if (2 === count($this->keywords[$keyword])) {
                        $msgid_plural = str_replace("\\$delimiter", $delimiter, $matches[2][$this->keywords[$keyword][1] - 1]);
                        $this->strings[$msgid] = new PoeditPluralString($msgid, $msgid_plural);
                    } else {
                        $this->strings[$msgid] = new PoeditString($msgid);
                    }
                }

                // add line reference to newly created or already existing string
                $this->strings[$msgid]->addReference($comment);
            }
        }

        fclose($handle);

        return $this->strings;
    }
}