#!/usr/bin/php
<?php
/*
This is free and unencumbered software released into the public domain.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a compiled
binary, for any purpose, commercial or non-commercial, and by any
means.

In jurisdictions that recognize copyright laws, the author or authors
of this software dedicate any and all copyright interest in the
software to the public domain. We make this dedication for the benefit
of the public at large and to the detriment of our heirs and
successors. We intend this dedication to be an overt act of
relinquishment in perpetuity of all present and future rights to this
software under copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

For more information, please refer to <http://unlicense.org>
*/




class Wave {
    private $sampleRate;
    private $samples;
    function __construct($sampleRate) {
        $this->sampleRate = $sampleRate;
        $this->samples = array();
    }

    public function addTone($freq, $dur, $ramp_up_dur, $ramp_down_dur) {
        $samplesCount = $dur * $this->sampleRate;
        $ramp_up_samples_count = $ramp_up_dur * $this->sampleRate ;
        $ramp_down_samples_count = $ramp_down_dur * $this->sampleRate ;
        // fwrite(STDERR,  "\n\n\n\n\n\n-------\n") ;

        $amplitude = 65536/2 * .9;
        $w = 2 * pi() * $freq / $this->sampleRate;

        // for ($n = 0; $n < $samplesCount; $n++) {
        //     $sample_amplitude = $amplitude ;
        //     if( $n<$ramp_up_samples_count ) {
        //         $sample_amplitude = $amplitude * ($n/$ramp_up_samples_count) ;
        //     } else if( $n>=($samplesCount-$ramp_down_samples_count) ) {
        //         $sample_amplitude = $amplitude * (($samplesCount-$ramp_down_samples_count)/$n) ;
        //     }
        //     fwrite(STDERR,  $sample_amplitude. "\n" );
        //     $this->samples[] = (int)($sample_amplitude *  sin($n * $w));
        // }

        for( $n=0 ; $n<$ramp_up_samples_count ; $n++ ) {
            $sample_amplitude = $amplitude * ($n/$ramp_up_samples_count) ;
            // fwrite(STDERR,  $sample_amplitude. "\n" );
            $this->samples[] = (int)($sample_amplitude *  sin($n * $w));
        }
        for( $n=$ramp_up_samples_count ; $n<($samplesCount-$ramp_down_samples_count) ; $n++ ) {
            $sample_amplitude = $amplitude ;
            // fwrite(STDERR,  $sample_amplitude. "\n" );
            $this->samples[] = (int)($sample_amplitude *  sin($n * $w));
        }
        $ramp_down_sample_index = $ramp_down_samples_count ;
        for( $n=($samplesCount-$ramp_down_samples_count) ; $n<$samplesCount ; $n++ ) {
            $sample_amplitude = $amplitude * ($ramp_down_sample_index/$ramp_down_samples_count) ;
            $ramp_down_sample_index-- ;
            // fwrite(STDERR,  $sample_amplitude. "\n" );
            $this->samples[] = (int)($sample_amplitude *  sin($n * $w));
        }
    }

    public function writeWav() {
        $bps = 16; //bits per sample
        $Bps = $bps/8; //bytes per sample /// I EDITED
        return call_user_func_array("pack",
            array_merge(array("VVVVVvvVVvvVVv*"),
                array(//header
                    0x46464952, //RIFF
                    2*count($this->samples) + 38,
                    0x45564157, //WAVE
                    0x20746d66, //"fmt " (chunk)
                    16, //chunk size
                    1, //compression
                    1, //nchannels
                    $this->sampleRate,
                    $Bps*$this->sampleRate, //bytes/second
                    $Bps, //block align
                    $bps, //bits/sample
                    0x61746164, //"data"
                    count($this->samples)*2 //chunk size
                ),
                $this->samples //data
            )
        );
    }

    public function outWav() {
        $data = $this->writeWav();
        // header('Content-type: audio/wav');
        // header('Content-length: '.strlen($data));
        echo $data;
    }

    public function outMp3() {
        $proc = proc_open('lame - -', array(
                0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
                1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
                2 => array("file", "/dev/null", "a") // stderr is a file to write to
            ), $pipes, '/var/www/htdocs', array());

        fwrite($pipes[0], $this->writeWav());
        fclose($pipes[0]);
        $ret = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);

        // header('Content-type: audio/mp3');
        // header('Content-length: '.strlen($ret));
        echo $ret;
    }
}


function pick_furthest_frequency( $previous_frequency, $choices ) {
    if( abs($previous_frequency-$choices[0]) > abs($previous_frequency-$choices[1]) ) {
        return $choices[0] ;
    }
    return $choices[1] ;
}

$control_frequency = 19100 ;
$frequencies = array(
    0=>array(19200,19300),
    1=>array(19300,19400),
    2=>array(19400,19500),
    3=>array(19500,19600),
    4=>array(19600,19700),
    5=>array(19700,19800),
    6=>array(19800,19900),
    7=>array(19900,20000),
    8=>array(20000,20100),
    9=>array(20100,20200)
) ;

$code = $argv[1] ;

$song = array() ;

// control
$song[] = 19100 ;
$song[] = 19200 ;

// payload
$previous_frequency = $song[count($song)-1] ;
$checksum = 0 ;
for( $i=0 ; $i<strlen($code) ; $i++ ) {
    $char = intval( $code[$i] ) ;
    $checksum += $char ;
    $previous_frequency = pick_furthest_frequency( $previous_frequency, $frequencies[$char] ) ;
    $song[] = $previous_frequency ;
}

// checksum
$checksum = strval( $checksum ) ;
// echo "checksum: {$checksum}\n" ;
if( strlen($checksum)==1 ) {
    $checksum = "0" . $checksum ;
}
$char = intval( $checksum[0] ) ;
$previous_frequency = pick_furthest_frequency( $previous_frequency, $frequencies[$char] ) ;
$song[] = $previous_frequency ;
$char = intval( $checksum[1] ) ;
$previous_frequency = pick_furthest_frequency( $previous_frequency, $frequencies[$char] ) ;
$song[] = $previous_frequency ;

// $song = array(1000, 1500, 1200);
$duration = .05;

// print_r( $song ) ;

$wav = new Wave( 49000 ) ;
for( $repeat=10 ; $repeat>0 ; $repeat-- ) {
    for( $i=0; $i<count($song); $i++ ) {
        $wav->addTone( $song[$i], $duration, 0.01, 0.01 ) ;
    }
}
$wav->outWav() ;

?>