<?php

namespace Solbeg\FilesManager\Contracts;

interface Validator
{
    /**
     * This method must validate `$source` file.
     * 
     * If file passed then `null` must be returned as result.
     * Otherwise the method must return string error message.
     * 
     * @param \Solbeg\FilesManager\Contracts\FileSource $source
     * @return string|null error message or null if file passed.
     */
    public function validate(FileSource $source);

    /**
     * The method must return true or false,
     * whether the validation should be skipped,
     * if file has been already failed by another validators.
     * 
     * @return boolean
     */
    public function skipOnError();
}
