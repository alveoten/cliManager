<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 20/06/18
 * Time: 15.31
 */

namespace Tabusoft\CliManager;

use Aura\Cli\CliFactory;
use Aura\Cli\Context\OptionFactory;
use Aura\Cli\Exception;
use Aura\Cli\Help;

class CliManager
{
    private $options = [];

    private $cli_factory;

    private $stdio;

    private $help;

    private $summary;

    private $usage;

    private $description;

    private $context;

    private $required;

    private $non_duplex;

    private $error_message;

    private $validators;

    public function __construct($options, $summary, $usage, $description, $required = [], $non_duplex = [], $validators = [] )
    {
        $this->summary = $summary;
        $this->options = $options;
        $this->usage = $usage;
        $this->required = $required;
        $this->non_duplex = $non_duplex;
        $this->description = $description;

        $this->cli_factory = new CliFactory;
        $this->stdio = $this->cli_factory->newStdio();
        $this->help = new Help(new OptionFactory);

        $this->help->setSummary($summary);
        $this->help->setUsage($usage);
        $this->help->setOptions( $options );
        $this->help->setDescr($description);

        $this->context = $this->cli_factory->newContext($GLOBALS);

        foreach ($validators as $option => $validator){
            if(!is_callable($validator)){
                throw new Exception("invalid validator lambda function for options: {$option}");
            }
        }

        $this->validators = $validators;

    }

    public function outLn($str){
        return $this->stdio->outln($str);
    }

    public function getOpt(){
        return ($this->context->getopt(array_keys($this->help->getOptions())))->get();
    }

    public function getValidity(): bool
    {

        $get_opt = $this->getOpt();

        foreach ($this->required as $r) {
            if (!isset($get_opt[$r])) {
                $this->error_message = "Need all required, {$r} not found ";
                return false;
            }
        }

        foreach ($this->non_duplex as $row) {

            $num_row = 0;
            foreach($row as $elem_to_find){
                if(isset($get_opt[$elem_to_find])){
                    $num_row++;
                }
            }

            if ( $num_row > 1) {
                $this->error_message = "Some element can't be used toghether: " . implode(", ", $row);
                return false;
            }

        }

        foreach($this->validators as $option => $validator){
            //validator can return true, otherwise the message error.
            $resultOrMessage = $validator( $get_opt[$option]??null );
            if($resultOrMessage !== true){
                $this->error_message = $resultOrMessage;
                return false;
            }
        }

        return true;
    }

    public function getError(){
        return $this->error_message;
    }

    public function outHelp(){
        $this->outLn( $this->help->getHelp( $GLOBALS["argv"][0]) );
    }

    public function outError(){
        $this->outLN("<<red>>".$this->error_message."<<reset>>");
    }
}