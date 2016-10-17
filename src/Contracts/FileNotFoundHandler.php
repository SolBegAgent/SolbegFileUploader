<?php

namespace Bicycle\FilesManager\Contracts;

interface FileNotFoundHandler
{
    /**
     * @param FileNotFoundException $exception
     * @return FileSource|boolean|null Result may be one of the followings:
     *  - FileSource: handlers process will be stopped, this file source (just with $format === null) will be used to get value
     *  - true: handlers process will be stopped, value from primary source will be taken again
     *  - false: handlers process will be stopped, it means value cannot be fetched, exception will be thrown
     *  - null: handlers process will be continued
     */
    public function handle(FileNotFoundException $exception);
}
