<?php
require_once BASEDIR.'/server/wwtest/ngrams/NGramsBookData.class.php';

class NGramsBook extends NGramsBookData
{
	public function __construct()
	{
		$table = 
array (
  'Have' => 
  array (
    'you' => 1,
  ),
  'you' => 
  array (
    'ever' => 1,
    'can' => 1,
    'think.' => 1,
    'won’t' => 1,
    'fly' => 1,
  ),
  'ever' => 
  array (
    'dreamt' => 1,
  ),
  'dreamt' => 
  array (
    'about' => 1,
  ),
  'about' => 
  array (
    'the' => 1,
  ),
  'the' => 
  array (
    'day' => 1,
    'Sky.”' => 1,
    'good' => 1,
    'Lemelson-MIT' => 1,
    'AirScooter,' => 1,
    'AirScooter' => 1,
    'motorcycle-like' => 1,
    'two' => 1,
    'wings' => 1,
    'potential' => 1,
    'White' => 1,
    'future' => 1,
    'new' => 1,
  ),
  'day' => 
  array (
    'you' => 1,
    'may' => 1,
  ),
  'can' => 
  array (
    'buzz' => 1,
    'fly' => 1,
    'be' => 1,
  ),
  'buzz' => 
  array (
    'around' => 1,
  ),
  'around' => 
  array (
    'in' => 1,
    '--' => 1,
  ),
  'in' => 
  array (
    'your' => 1,
    'the' => 2,
    'their' => 1,
    'non-restricted' => 1,
    'on' => 1,
    '1956,' => 1,
    'bad' => 1,
  ),
  'your' => 
  array (
    'very' => 1,
  ),
  'very' => 
  array (
    'own' => 2,
  ),
  'own' => 
  array (
    'flying' => 1,
    'vehicles.' => 1,
  ),
  'flying' => 
  array (
    'machine?' => 1,
    'machine.' => 1,
    'machines.' => 1,
    'machines' => 1,
    'easy,' => 1,
  ),
  'machine?' => 
  array (
    'Well,' => 1,
  ),
  'Well,' => 
  array (
    'that' => 1,
  ),
  'that' => 
  array (
    'day' => 1,
    'is,' => 1,
    'can' => 1,
  ),
  'may' => 
  array (
    'be' => 1,
  ),
  'be' => 
  array (
    'sooner' => 1,
    'able' => 1,
    'put' => 1,
  ),
  'sooner' => 
  array (
    'than' => 1,
  ),
  'than' => 
  array (
    'you' => 1,
  ),
  'think.' => 
  array (
    'The' => 1,
  ),
  'The' => 
  array (
    'folks' => 1,
  ),
  'folks' => 
  array (
    'at' => 1,
  ),
  'at' => 
  array (
    'NASA' => 1,
    55 => 1,
  ),
  'NASA' => 
  array (
    'have' => 1,
    'has' => 1,
  ),
  'have' => 
  array (
    'built' => 1,
    'tried' => 1,
  ),
  'built' => 
  array (
    'something' => 1,
    'in' => 1,
  ),
  'something' => 
  array (
    'called' => 1,
  ),
  'called' => 
  array (
    '“The' => 1,
    'the' => 2,
  ),
  '“The' => 
  array (
    'Highway' => 1,
  ),
  'Highway' => 
  array (
    'in' => 1,
  ),
  'Sky.”' => 
  array (
    'It\'s' => 1,
  ),
  'It\'s' => 
  array (
    'a' => 1,
    'called' => 1,
  ),
  'a' => 
  array (
    'computer' => 1,
    'lot' => 1,
    'half-million' => 1,
    'brand' => 1,
    'hilltop' => 1,
    'pilot’s' => 1,
    'plan' => 1,
    'reality.' => 1,
    'flight' => 1,
    'new' => 1,
  ),
  'computer' => 
  array (
    'system' => 2,
  ),
  'system' => 
  array (
    'designed' => 1,
    'that' => 1,
  ),
  'designed' => 
  array (
    'to' => 1,
  ),
  'to' => 
  array (
    'let' => 1,
    'buy.' => 1,
    'honor' => 1,
    'demonstrate' => 1,
    '10,000' => 1,
    'sell' => 1,
    'cash' => 1,
    'catch' => 1,
    'fly' => 1,
    'really' => 1,
    'make' => 1,
  ),
  'let' => 
  array (
    'millions' => 1,
  ),
  'millions' => 
  array (
    'of' => 2,
  ),
  'of' => 
  array (
    'people' => 2,
    'those' => 1,
    'his' => 1,
    'inventors' => 1,
    'them' => 1,
    'NASA’s' => 1,
    'aviation.' => 1,
  ),
  'people' => 
  array (
    'fly' => 1,
    'are' => 1,
    'is' => 1,
  ),
  'fly' => 
  array (
    'whenever' => 1,
    'for' => 1,
    'it' => 1,
    'in' => 1,
  ),
  'whenever' => 
  array (
    'they' => 1,
  ),
  'they' => 
  array (
    'please,' => 2,
    'failed' => 1,
    'were' => 1,
  ),
  'please,' => 
  array (
    'and' => 1,
    'in' => 1,
  ),
  'and' => 
  array (
    'take' => 1,
    'land' => 1,
    'self-taught' => 1,
    'go' => 1,
    'the' => 1,
    'it' => 1,
    'haul' => 1,
    'hard' => 1,
    'has' => 1,
    'will' => 1,
  ),
  'take' => 
  array (
    'off' => 1,
  ),
  'off' => 
  array (
    'and' => 1,
  ),
  'land' => 
  array (
    'from' => 1,
  ),
  'from' => 
  array (
    'wherever' => 1,
    'the' => 1,
    'millions' => 1,
  ),
  'wherever' => 
  array (
    'they' => 1,
  ),
  'their' => 
  array (
    'very' => 1,
  ),
  'vehicles.' => 
  array (
    'And' => 1,
  ),
  'And' => 
  array (
    'here’s' => 1,
    'he’s' => 1,
    'that’s' => 1,
  ),
  'here’s' => 
  array (
    'the' => 1,
  ),
  'good' => 
  array (
    'news' => 1,
  ),
  'news' => 
  array (
    '--' => 1,
  ),
  '--' => 
  array (
    'a' => 2,
    'that' => 1,
  ),
  'lot' => 
  array (
    'of' => 2,
  ),
  'are' => 
  array (
    'building' => 1,
  ),
  'building' => 
  array (
    'machines' => 1,
  ),
  'machines' => 
  array (
    'you’ll' => 1,
    'a' => 1,
  ),
  'you’ll' => 
  array (
    'be' => 1,
  ),
  'able' => 
  array (
    'to' => 1,
  ),
  'buy.' => 
  array (
    'One' => 1,
  ),
  'One' => 
  array (
    'of' => 1,
  ),
  'those' => 
  array (
    'people' => 1,
  ),
  'is' => 
  array (
    'an' => 1,
    'controlled' => 1,
    'one' => 1,
  ),
  'an' => 
  array (
    'inventor' => 1,
  ),
  'inventor' => 
  array (
    'named' => 1,
    'Woody' => 1,
  ),
  'named' => 
  array (
    'Woody' => 1,
  ),
  'Woody' => 
  array (
    'Norris.' => 1,
    'Norris' => 1,
  ),
  'Norris.' => 
  array (
    'This' => 1,
  ),
  'This' => 
  array (
    'week,' => 1,
  ),
  'week,' => 
  array (
    'he' => 1,
  ),
  'he' => 
  array (
    'will' => 1,
    'worked' => 1,
  ),
  'will' => 
  array (
    'receive' => 1,
    'make' => 1,
    'manage' => 1,
  ),
  'receive' => 
  array (
    'America’s' => 1,
  ),
  'America’s' => 
  array (
    'top' => 1,
  ),
  'top' => 
  array (
    'prize' => 1,
  ),
  'prize' => 
  array (
    'for' => 1,
    'to' => 1,
  ),
  'for' => 
  array (
    'invention.' => 1,
    60 => 1,
    2 => 1,
    '$50,000.' => 1,
  ),
  'invention.' => 
  array (
    'It’s' => 1,
  ),
  'It’s' => 
  array (
    'called' => 1,
  ),
  'Lemelson-MIT' => 
  array (
    'award' => 1,
  ),
  'award' => 
  array (
    '--' => 1,
  ),
  'half-million' => 
  array (
    'dollar' => 1,
  ),
  'dollar' => 
  array (
    'cash' => 1,
  ),
  'cash' => 
  array (
    'prize' => 1,
    'in' => 1,
  ),
  'honor' => 
  array (
    'his' => 1,
  ),
  'his' => 
  array (
    'life’s' => 1,
    'test' => 1,
  ),
  'life’s' => 
  array (
    'work,' => 1,
  ),
  'work,' => 
  array (
    'which' => 1,
  ),
  'which' => 
  array (
    'includes' => 1,
  ),
  'includes' => 
  array (
    'a' => 1,
  ),
  'brand' => 
  array (
    'new' => 1,
  ),
  'new' => 
  array (
    'personal' => 1,
    'computer' => 1,
    'airborne' => 1,
    'traffic' => 1,
  ),
  'personal' => 
  array (
    'flying' => 3,
  ),
  'machine.' => 
  array (
    'It\'s' => 1,
  ),
  'AirScooter,' => 
  array (
    'and' => 1,
  ),
  'self-taught' => 
  array (
    'inventor' => 1,
  ),
  'Norris' => 
  array (
    'says' => 2,
    'tells' => 1,
  ),
  'says' => 
  array (
    'it' => 2,
    'you' => 1,
  ),
  'it' => 
  array (
    'goes' => 2,
    'stops,' => 1,
    'forward' => 1,
    'back' => 1,
    'under' => 1,
    'for' => 1,
    'will' => 1,
  ),
  'goes' => 
  array (
    'on' => 1,
    'back.' => 1,
  ),
  'on' => 
  array (
    'sale' => 1,
    'a' => 1,
    'personal' => 1,
    'because' => 1,
    'the' => 1,
  ),
  'sale' => 
  array (
    'later' => 1,
  ),
  'later' => 
  array (
    'this' => 1,
  ),
  'this' => 
  array (
    'year.' => 1,
  ),
  'year.' => 
  array (
    'Norris,' => 1,
  ),
  'Norris,' => 
  array (
    '66,' => 1,
  ),
  '66,' => 
  array (
    'asked' => 1,
  ),
  'asked' => 
  array (
    'one' => 1,
  ),
  'one' => 
  array (
    'of' => 2,
  ),
  'test' => 
  array (
    'pilots' => 1,
  ),
  'pilots' => 
  array (
    'to' => 1,
  ),
  'demonstrate' => 
  array (
    'the' => 1,
  ),
  'AirScooter' => 
  array (
    'for' => 1,
  ),
  60 => 
  array (
    'Minutes' => 1,
  ),
  'Minutes' => 
  array (
    'on' => 1,
  ),
  'hilltop' => 
  array (
    'outside' => 1,
  ),
  'outside' => 
  array (
    'San' => 1,
  ),
  'San' => 
  array (
    'Diego,' => 1,
  ),
  'Diego,' => 
  array (
    'Calif.' => 1,
  ),
  'Calif.' => 
  array (
    'It' => 1,
  ),
  'It' => 
  array (
    'can' => 1,
  ),
  2 => 
  array (
    'hours' => 1,
  ),
  'hours' => 
  array (
    'at' => 1,
  ),
  55 => 
  array (
    'mph,' => 1,
  ),
  'mph,' => 
  array (
    'and' => 1,
  ),
  'go' => 
  array (
    'up' => 1,
  ),
  'up' => 
  array (
    'to' => 1,
    'with' => 1,
    'there' => 1,
  ),
  '10,000' => 
  array (
    'feet' => 1,
  ),
  'feet' => 
  array (
    'above' => 1,
    'in' => 1,
  ),
  'above' => 
  array (
    'sea' => 1,
  ),
  'sea' => 
  array (
    'level.' => 1,
  ),
  'level.' => 
  array (
    '"Look' => 1,
  ),
  '"Look' => 
  array (
    'how' => 1,
  ),
  'how' => 
  array (
    'quickly' => 1,
  ),
  'quickly' => 
  array (
    'it' => 1,
  ),
  'stops,' => 
  array (
    'hovers,' => 1,
  ),
  'hovers,' => 
  array (
    'sideways,' => 1,
  ),
  'sideways,' => 
  array (
    'sideways,' => 1,
    'straight' => 1,
  ),
  'straight' => 
  array (
    'down,"' => 1,
  ),
  'down,"' => 
  array (
    'Norris' => 1,
  ),
  'tells' => 
  array (
    'Simon.' => 1,
  ),
  'Simon.' => 
  array (
    'Everything' => 1,
  ),
  'Everything' => 
  array (
    'is' => 1,
  ),
  'controlled' => 
  array (
    'from' => 1,
  ),
  'motorcycle-like' => 
  array (
    'handle' => 1,
  ),
  'handle' => 
  array (
    'bar.' => 1,
  ),
  'bar.' => 
  array (
    'Push' => 1,
  ),
  'Push' => 
  array (
    'it' => 2,
  ),
  'forward' => 
  array (
    'and' => 1,
  ),
  'two' => 
  array (
    'counter-rotating' => 1,
  ),
  'counter-rotating' => 
  array (
    'blades' => 1,
  ),
  'blades' => 
  array (
    'pivot' => 1,
  ),
  'pivot' => 
  array (
    'forward.' => 1,
  ),
  'forward.' => 
  array (
    'Push' => 1,
  ),
  'back' => 
  array (
    'and' => 1,
  ),
  'back.' => 
  array (
    'Norris' => 1,
  ),
  'won’t' => 
  array (
    'need' => 1,
  ),
  'need' => 
  array (
    'a' => 1,
  ),
  'pilot’s' => 
  array (
    'license' => 1,
  ),
  'license' => 
  array (
    'if' => 1,
  ),
  'if' => 
  array (
    'you' => 1,
  ),
  'under' => 
  array (
    400 => 1,
  ),
  400 => 
  array (
    'feet' => 1,
  ),
  'non-restricted' => 
  array (
    'air' => 1,
  ),
  'air' => 
  array (
    'space.' => 1,
  ),
  'space.' => 
  array (
    'And' => 1,
  ),
  'he’s' => 
  array (
    'going' => 1,
  ),
  'going' => 
  array (
    'to' => 1,
  ),
  'sell' => 
  array (
    'it' => 1,
  ),
  '$50,000.' => 
  array (
    'A' => 1,
  ),
  'A' => 
  array (
    'lot' => 1,
  ),
  'inventors' => 
  array (
    'have' => 1,
  ),
  'tried' => 
  array (
    'to' => 1,
  ),
  'machines.' => 
  array (
    'One,' => 1,
  ),
  'One,' => 
  array (
    'built' => 1,
  ),
  '1956,' => 
  array (
    'was' => 1,
  ),
  'was' => 
  array (
    'known' => 1,
    'no' => 1,
  ),
  'known' => 
  array (
    'as' => 1,
  ),
  'as' => 
  array (
    'Molt' => 1,
  ),
  'Molt' => 
  array (
    'Taylor’s' => 1,
  ),
  'Taylor’s' => 
  array (
    'Aerocar.' => 1,
  ),
  'Aerocar.' => 
  array (
    'You' => 1,
  ),
  'You' => 
  array (
    'could' => 1,
  ),
  'could' => 
  array (
    'detach' => 1,
  ),
  'detach' => 
  array (
    'the' => 1,
  ),
  'wings' => 
  array (
    'and' => 1,
  ),
  'haul' => 
  array (
    'them' => 1,
  ),
  'them' => 
  array (
    'behind' => 1,
    'buzzing' => 1,
  ),
  'behind' => 
  array (
    'you.' => 1,
  ),
  'you.' => 
  array (
    'But' => 1,
  ),
  'But' => 
  array (
    'they' => 1,
  ),
  'failed' => 
  array (
    'to' => 1,
  ),
  'catch' => 
  array (
    'on' => 1,
  ),
  'because' => 
  array (
    'they' => 1,
    'NASA' => 1,
  ),
  'were' => 
  array (
    'too' => 1,
  ),
  'too' => 
  array (
    'expensive' => 1,
  ),
  'expensive' => 
  array (
    'and' => 1,
  ),
  'hard' => 
  array (
    'to' => 1,
  ),
  'bad' => 
  array (
    'weather.' => 1,
  ),
  'weather.' => 
  array (
    'More' => 1,
  ),
  'More' => 
  array (
    'important,' => 1,
  ),
  'important,' => 
  array (
    'there' => 1,
  ),
  'there' => 
  array (
    'was' => 1,
  ),
  'no' => 
  array (
    'way' => 1,
  ),
  'way' => 
  array (
    'to' => 1,
  ),
  'really' => 
  array (
    'manage' => 1,
  ),
  'manage' => 
  array (
    'all' => 2,
  ),
  'all' => 
  array (
    'the' => 2,
  ),
  'potential' => 
  array (
    'traffic' => 1,
  ),
  'traffic' => 
  array (
    'from' => 1,
    'up' => 1,
  ),
  'buzzing' => 
  array (
    'around' => 1,
  ),
  'is,' => 
  array (
    'until' => 1,
  ),
  'until' => 
  array (
    'now.' => 1,
  ),
  'now.' => 
  array (
    'And' => 1,
  ),
  'that’s' => 
  array (
    'because' => 1,
  ),
  'has' => 
  array (
    'come' => 1,
    'served' => 1,
  ),
  'come' => 
  array (
    'up' => 1,
  ),
  'with' => 
  array (
    'a' => 1,
  ),
  'plan' => 
  array (
    'to' => 1,
  ),
  'make' => 
  array (
    'personal' => 1,
    'flying' => 1,
  ),
  'reality.' => 
  array (
    'Bruce' => 1,
  ),
  'Bruce' => 
  array (
    'Holmes' => 1,
  ),
  'Holmes' => 
  array (
    'is' => 1,
  ),
  'NASA’s' => 
  array (
    'chief' => 1,
  ),
  'chief' => 
  array (
    'strategists' => 1,
  ),
  'strategists' => 
  array (
    'and' => 1,
  ),
  'served' => 
  array (
    'in' => 1,
  ),
  'White' => 
  array (
    'House,' => 1,
  ),
  'House,' => 
  array (
    'where' => 1,
  ),
  'where' => 
  array (
    'he' => 1,
  ),
  'worked' => 
  array (
    'on' => 1,
  ),
  'future' => 
  array (
    'of' => 1,
  ),
  'aviation.' => 
  array (
    'He' => 1,
  ),
  'He' => 
  array (
    'showed' => 1,
    'says' => 1,
  ),
  'showed' => 
  array (
    'Simon' => 1,
  ),
  'Simon' => 
  array (
    'a' => 1,
  ),
  'flight' => 
  array (
    'simulator,' => 1,
  ),
  'simulator,' => 
  array (
    'a' => 1,
  ),
  'put' => 
  array (
    'into' => 1,
  ),
  'into' => 
  array (
    'any' => 1,
  ),
  'any' => 
  array (
    'new' => 1,
  ),
  'airborne' => 
  array (
    'vehicle.' => 1,
  ),
  'vehicle.' => 
  array (
    'He' => 1,
  ),
  'easy,' => 
  array (
    'and' => 1,
  ),
);
		parent::__construct( 2, $table, ' ' );
	}
}