# PHP Chatbot
## Arkitektur

### Flyt
	1.	Bruker skriver spørsmål i UI (HTML/JS).
	2.	api/chatbot.php tar imot tekst → sender til BotEngine (PHP).
	3.	BotEngine gjør:
		(a) Regelbasert match (enkle nøkkelord/regex)
		(b) FULLTEXT-søk i MySQL (BM25-lignende ranking via MATCH ... AGAINST)
		(c) Fuzzy fallback (PHP similar_text/Levenshtein) mot kjente mønstre
	4.	Svar returneres som JSON → vises i UI.
	5.	Alt logges i DB (nyttig til evaluering og forbedring).
