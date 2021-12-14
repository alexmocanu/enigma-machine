<?php
//Based on details I gathered from here.
//https://www.apprendre-en-ligne.net/crypto/bibliotheque/PDF/paperEnigma.pdf
//https://www.youtube.com/watch?v=UKbP3Rjxhy0
class Enigma {

    //Input/Output Panel
    protected $inputOutput = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];
    
    //Redirects a signal back into the rotor system (io/panel->rotors-reflector->rotors again->io/panel)
    protected $reflector = ["A", "B", "C", "D", "E", "F", "G", "D", "I", "J", "K", "G", "M", "K", "M", "I", "E", "B", "F", "T", "C", "V", "V", "J", "A", "T"];

    //Rotor configuration, hardcoded for convenience
    protected $rotors = [
        [
            ["A", "E"], ["B", "K"], ["C", "M"], ["D", "F"], ["E", "L"], ["F", "G"], ["G", "D"], ["H", "Q"], ["I", "V"], ["J", "Z"], ["K", "N"], ["L", "T"],
            ["M", "O"], ["N", "W"], ["O", "Y"], ["P", "H"], ["Q", "X"], ["R", "U"], ["S", "S"], ["T", "P"], ["U", "A"], ["V", "I"], ["W", "B"], ["X", "R"],
            ["Y", "C"], ["Z", "J"],
        ],
        [
            ["A", "A"], ["B", "J"], ["C", "D"], ["D", "K"], ["E", "S"], ["F", "I"], ["G", "R"], ["H", "U"], ["I", "X"], ["J", "B"], ["K", "L"], ["L", "H"],
            ["M", "W"], ["N", "T"], ["O", "M"], ["P", "C"], ["Q", "Q"], ["R", "G"], ["S", "Z"], ["T", "N"], ["U", "P"], ["V", "Y"], ["W", "F"], ["X", "V"],
            ["Y", "O"], ["Z", "E"],
        ], [
            ["A", "B"], ["B", "D"], ["C", "F"], ["D", "H"], ["E", "J"], ["F", "L"], ["G", "C"], ["H", "P"], ["I", "R"], ["J", "T"], ["K", "X"], ["L", "V"],
            ["M", "Z"], ["N", "N"], ["O", "Y"], ["P", "E"], ["Q", "I"], ["R", "W"], ["S", "G"], ["T", "A"], ["U", "K"], ["V", "M"], ["W", "U"], ["X", "S"],
            ["Y", "Q"], ["Z", "O"],
        ]
    ];
    
    //When a rotor reaches one of these positions the one next to it advances one position, just like in a mechanical counter
    protected $flip = ["Q", "E", "V"];
    
    //encryption key - array containing a letter for each rotor - basically the initial position of each rotor    
    protected $key;

    public function __construct($key) {
        $this->key = $key;
    }

    /**
     * Rotate the rotors until the encryption key letters are at the top
     * I know it can be done better, but I wanted to emulate the real process as close as possible
     */
    public function configureRotors() {
        foreach($this->rotors as $key=>&$rotor) {
            if($rotor[0][0] != $this->key[$key]) {
                while($rotor[0][0] != $this->key[$key]) {
                    $rotor[] = array_shift($rotor);
                }
            }
        }
    }
    
    /*
     * On the actual machine each letter signal starts at the keyboard, goes through the rotors from right to left, bounces off the reflector back into the rotors, this time from left to right, until it reaches the output panel (a set of lights)
     * In our case the input and output panels are the same.
     * 
     * Before we encode a letter we advance the rightmost rotor one step and propagate to the others if needed, similar to a mechanical counter.
     * The rightmost rotor (near the i/o panel) always advances one position before encoding a letter. 
     * The other rotors advance one position from right to left only when the rotor on their right reaches the flip position.
     *
     * Same as above, it can be done better, but I wanted to emulate the real process as close as possible
     */
    public function passLetter($letter) {
        
        $this->advanceRotors();
        
        //echo "Input: ".$letter."\n";
        
        $countRotors = count($this->rotors);
        $countLetters = count($this->rotors[0]);
        
        $keyOnInput = 0;
        for($i=0;$i<count($this->inputOutput);$i++) {
            if($letter == $this->inputOutput[$i]) {
                $keyOnInput = $i;
            }
        }
        
        //echo "Position on input panel = ".$keyOnInput."\n";        
        
        $currentPosition = $keyOnInput;
        for($numRotor=$countRotors-1;$numRotor>=0;$numRotor--) {
            //echo "Rotor ".($numRotor+1).", ";            
            $letterOnRotor = $this->rotors[$numRotor][$currentPosition];
            $currentPosition = $this->getLeftPositionOnRotor($numRotor, $letterOnRotor[1]);
            //echo $currentPosition."(". $letterOnRotor[1].") \n";                    
        }
        
        $letterOnReflector = $this->reflector[$currentPosition];
        //echo "Refflector position: ".$currentPosition.": ".$letterOnReflector."\n";
        
        for($i=0;$i<count($this->reflector);$i++) {
            if($letterOnReflector == $this->reflector[$i] && $i != $currentPosition) {
                $currentPosition = $i;
                break;
            }            
        }
        
        //echo "New position on reflector: ".$currentPosition."\n";
        
        for($numRotor = 0; $numRotor < $countRotors; $numRotor++) {
            //echo "Rotor ".($numRotor+1).", ";
            $letterOnRotor = $this->rotors[$numRotor][$currentPosition];            
            $currentPosition = $this->getRightPositionOnRotor($numRotor, $letterOnRotor[0]);
            //echo $currentPosition."(". $letterOnRotor[0].") \n";                    
        }
        
        $output = $this->inputOutput[$currentPosition];
        
        //echo "Rezultat: ".$output."\n";
        
        return $output;        
        
    }
    
    /** Helper functions to get rotor positions and values */
    public function getLetterOnRotorAtPosition($rotor, $position) {
        return $this->rotors[$rotor][$position];
    }
    
    public function getLeftPositionOnRotor($rotor, $letter) {
        foreach($this->rotors[$rotor] as $key=>$value) {
            if($value[0] == $letter) {
                return $key;
            }
        }                
    }
    
    public function getRightPositionOnRotor($rotor, $letter) {
        foreach($this->rotors[$rotor] as $key=>$value) {
            if($value[1] == $letter) {
                return $key;
            }
        }                
    }

    //Display the reflector, current status of the rotors and the input/output panel
    public function printRotors() {

        $countRotors = count($this->rotors);
        $countRotorEntries = count($this->rotors[0]);
        
        echo "KEY\tREF\tR1\tR2\tR3\tIO\n";
        
        for($numLetter=0;$numLetter<$countRotorEntries;$numLetter++) {
            echo $numLetter.":\t".$this->reflector[$numLetter]."\t";
            for($numRotor=0;$numRotor<$countRotors;$numRotor++) {
                echo $this->rotors[$numRotor][$numLetter][0].'->'.$this->rotors[$numRotor][$numLetter][1]."\t";
            }
            echo $this->inputOutput[$numLetter];
            echo "\n";
        }
    }

    //Advances the rightmost rotor one step and propagates to the others if needed from right to left
    public function advanceRotors() {
        
        $countRotors = count($this->rotors);
        for($numRotor=$countRotors-1;$numRotor>=0;$numRotor--) {
            $this->advanceRotor($numRotor);
            if($this->rotors[$numRotor][0][0] != $this->flip[$numRotor]) {
                return;
            }                    
        }
    }
    
    //Rotate a rotor
    public function advanceRotor($rotor) {
        $this->rotors[$rotor][] =  array_shift($this->rotors[$rotor]);
    }
    
    //Translate a message    
    public function translate($message) {
        $this->configureRotors();
        $result = "";
        foreach (str_split($message) as $char) {
            $result.=$this->passLetter($char);
        }
        return $result;
    }
}

//initialize the enigma machine with the desired key (one letter per rotor)
$enigma = new Enigma(['M','C','K']);

//Translate the message
$result1 = $enigma->translate('ATTACKATDAWN');
echo "Result 1: ".$result1."\n"; //encrypted message
$result2 = $enigma->translate($result1);
echo "Result 2: ".$result2."\n"; //decrypted message
$result3 = $enigma->translate($result2);
echo "Result 3: ".$result3."\n"; //encrypted message...again
$result4 = $enigma->translate($result3);
echo "Result 4: ".$result4."\n"; //decrypted message...again
