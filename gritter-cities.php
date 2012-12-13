<?php

/**
 * Required for the [CITY] shortcode. Provide all cites and a random select one city function.
 */
if (!class_exists('Gritter_Plugin_Cities')) {

    class Gritter_Plugin_Cities {

        private $cities = array(
            'Berlin',
            'Hamburg',
            'München',
            'Köln',
            'Frankfurt am Main',
            'Stuttgart',
            'Düsseldorf',
            'Dortmund',
            'Essen',
            'Bremen',
            'Leipzig',
            'Dresden',
            'Hannover',
            'Nürnberg',
            'Duisburg',
            'Bochum',
            'Wuppertal',
            'Bonn',
            'Bielefeld',
            'Mannheim',
            'Karlsruhe',
            'Münster',
            'Wiesbaden',
            'Augsburg',
            'Aachen',
            'Mönchengladbach',
            'Gelsenkirchen',
            'Braunschweig',
            'Chemnitz',
            'Kiel',
            'Krefeld',
            'Halle (Saale)',
            'Magdeburg',
            'Freiburg im Breisgau',
            'Oberhausen',
            'Lübeck',
            'Erfurt',
            'Rostock',
            'Mainz',
            'Kassel',
            'Hagen',
            'Hamm',
            'Saarbrücken',
            'Mülheim an der Ruhr',
            'Ludwigshafen am Rhein',
            'Osnabrück',
            'Herne',
            'Oldenburg',
            'Leverkusen',
            'Solingen',
            'Potsdam',
            'Neuss',
            'Heidelberg',
            'Darmstadt',
            'Paderborn',
            'Regensburg',
            'Würzburg',
            'Ingolstadt',
            'Heilbronn',
            'Ulm',
            'Offenbach am Main',
            'Wolfsburg',
            'Göttingen',
            'Pforzheim',
            'Recklinghausen',
            'Bottrop',
            'Fürth',
            'Bremerhaven',
            'Reutlingen',
            'Remscheid',
            'Koblenz',
            'Erlangen',
            'Bergisch Gladbach',
            'Trier',
            'Jena',
            'Moers',
            'Siegen',
            'Hildesheim',
            'Cottbus',
            'Salzgitter',
        );
        public $city = '';

        public function __construct($random = false) {
            if ($random == TRUE)
                $this->generateRandom();
        }

        public function generateRandom() {
            do {
                $random = mt_rand(0, (count($this->cities) - 1));
            } while (!isset($this->cities[$random]));
            if (isset($this->cities[$random]))
                $this->city = $this->cities[$random];
        }

    }

}
?>
