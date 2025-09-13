<?php

class TestParsedown extends Parsedown
{
    public function getTextLevelElements(): array
    {
        return $this->textLevelElements;
    }
}
