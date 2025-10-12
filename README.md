## **1) Mappestruktur**

```
PHP_Chatbot/
├─ public/
│  ├─ index.html                # Chat-grensesnittet
│  ├─ style.css                 # Valgfritt design
│  └─ api/
│     └─ chatbot.php            # API-endepunkt (mottar spørsmål, sender svar)
│
├─ src/
│  ├─ BotEngine.php             # Kjerneklasse: styrer hele logikken
│  ├─ RuleMatcher.php           # Regelbasert matching (enkle nøkkelord)
│  ├─ FullTextSearch.php        # Databasesøk med FULLTEXT (faq + artikler)
│  ├─ FuzzyMatcher.php          # Fuzzy matching (similar_text / levenshtein)
│  ├─ ReplyFormatter.php        # Formaterer svar + eventuelle varsler
│  └─ db.php                    # Oppretter PDO-tilkobling
│
├─ db/
│  ├─ schema.sql                # Opprettelse av tabeller + indekser
│  └─ seed.sql                  # Startdata for FAQ og artikler
│
├─ admin/                       # (Valgfritt) enkel admin-side
│  ├─ index.php                 # CRUD for å legge inn/endre data
│  └─ auth.php                  # Enkel passordbeskyttelse
│
├─ docker-compose.yml
├─ .env                         # DB-passord og innstillinger
└─ README.md
```

---

## **2) Hva hver fil gjør (klasser, funksjoner og logikk)**

### **public/index.html**

- **Rolle:** Enkelt brukergrensesnitt
- **Funksjon:**
    - Brukeren skriver spørsmål
    - fetch() sender POST-forespørsel til api/chatbot.php
    - Viser svaret som chatboten returnerer
- **Valgfritt:** kan ha feilhåndtering, “typing”-animasjon, eller mørk modus

---

### **public/api/chatbot.php**

- **Rolle:** API-endepunkt / kontroller
- **Flyt:**
    1. Leser POST['message']
    2. Oppretter BotEngine-objekt
    3. Kaller $bot->answer($message, $sessionId)
    4. Returnerer JSON med {"reply": "..."}
- **Sikkerhet:**
    - Inputvalidering (lengde, spesialtegn)
    - Setter en unik session_id (cookie)
    - Ingen SQL eller sensitive data her

---

### **src/db.php**

- **Funksjon:**
    - Oppretter **én PDO-tilkobling** til databasen
    - Leser verdier fra .env
    - Returnerer en global funksjon db()
- **Inneholder:**
    - PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    - utf8mb4 for Unicode
- 📚 **Dokumentasjon:**
    - [PHP PDO – dokumentasjon](https://www.php.net/manual/en/book.pdo.php)
    - [Prepared statements](https://www.php.net/manual/en/pdo.prepared-statements.php)

---

### **src/BotEngine.php**

- **Klasse:** BotEngine
- **Metode:** answer(string $spørsmål, string $sessionId): string
- **Logikk:**
    1. Prøver RuleMatcher først (hurtigsvar)
    2. Deretter FullTextSearch (faq, artikler)
    3. Faller tilbake på FuzzyMatcher om ingen treff
    4. Logger samtalen (INSERT conversations)
    5. Returnerer svaret som streng

---

### **src/RuleMatcher.php**

- **Klasse:** RuleMatcher
- **Funksjon:**
    - Holder et array med nøkkelord og ferdige svar
    - Søker i brukerens spørsmål (stripos(), regex)
    - Returnerer første match
- **Brukes av:** BotEngine (først i kjeden)

---

### **src/FullTextSearch.php**

- **Klasse:** FullTextSearch
- **Funksjoner:**
    - bestFaq($q) → søker i faq
    - bestArticle($q) → søker i articles
    - logConversation($session, $spm, $svar) → lagrer samtale
- **SQL-eksempel:**

```
SELECT answer
FROM faq
WHERE MATCH(question, answer) AGAINST(:q IN NATURAL LANGUAGE MODE)
ORDER BY score DESC
LIMIT 1;
```

- 📚 **Dokumentasjon:**
    - [MySQL Fulltext Search](https://dev.mysql.com/doc/refman/8.0/en/fulltext-search.html)

---

### **src/FuzzyMatcher.php**

- **Klasse:** FuzzyMatcher
- **Funksjon:**
    - Sammenligner brukerens tekst med kjente mønstre
    - Bruker similar_text() for korte ord, levenshtein() for lengre
    - Treffer hvis prosent ≥ 40
- 📚 **Dokumentasjon:**
    - [similar_text()](https://www.php.net/manual/en/function.similar-text.php)
    - [levenshtein()](https://www.php.net/manual/en/function.levenshtein.php)

---

### **src/ReplyFormatter.php**

- **Klasse:** ReplyFormatter
- **Funksjon:**
    - Lager korte utdrag av artikler
    - Sikrer at tekst er UTF-8 og ren for HTML
    - Legger eventuelt på en liten advarsel (“ikke medisinsk råd”)

---

### **db/schema.sql**

- Oppretter tabeller:
    - **faq** (question, answer, tags)
    - **articles** (title, body, tags)
    - **conversations** (session_id, user_message, bot_reply, created_at)
- Legger til **FULLTEXT** på faq(question,answer) og articles(title,body)
- Bruker ENGINE=InnoDB og utf8mb4

---

### **db/seed.sql**

- Setter inn startdata (f.eks. 4–5 spørsmål/svar)
- Eksempel:

```
INSERT INTO faq (question, answer)
VALUES ('what is RPE?', 'RPE står for Rate of Perceived Exertion...');
```

---

### **admin/index.php**

### **(valgfritt)**

- Enkel side for å:
    - Se samtaler
    - Legge til/endre FAQ
    - Krever passord via auth.php

---

## **3) Kommunikasjonskart (enveis ↔ toveis)**

| **Komponent** | **Kommuniserer med** | **Retning** | **Hva sendes** |
| --- | --- | --- | --- |
| **index.html** | **chatbot.php** | → | POST (brukerspørsmål) |
| **chatbot.php** | **BotEngine** | ↔ | Spørsmål / svar-streng |
| **BotEngine** | **RuleMatcher** | → | Søker etter nøkkelord |
| **BotEngine** | **FullTextSearch** | ↔ | SELECT / INSERT |
| **BotEngine** | **FuzzyMatcher** | → | Tekstsammenligning |
| **FullTextSearch** | **MySQL** | ↔ | Databaseforespørsel |
| **chatbot.php** | **index.html** | → | JSON (svar) |
| **Admin (valgfritt)** | **FullTextSearch** | ↔ | CRUD-operasjoner |

**Tips til diagrammet ditt:**

- Bruk piler med navn på dataflyt (POST, JSON, SQL, String)
- Marker tydelig hvilke er **toveis (↔)** (f.eks. BotEngine ↔ FullTextSearch)
- Bruk ulike farger for: frontend (blå), backend (oransje), database (grønn)

---

## **4) Rekkefølge på implementasjon (med vanskelighetsgrad)**

| **Trinn** | **Fil(er)** | **Beskrivelse** | **Vanskelighet** |
| --- | --- | --- | --- |
| 1 | docker-compose.yml, .env | Sett opp miljø (PHP + MySQL) | 🟢 Lett |
| 2 | db.php | PDO-tilkobling + test | 🟢 Lett |
| 3 | schema.sql + seed.sql | Opprett tabeller og sett inn data | 🟢 Lett |
| 4 | RuleMatcher.php | Enkle regler (keywords → svar) | 🟡 Middels |
| 5 | FullTextSearch.php | Implementer MATCH ... AGAINST | 🟡 Middels |
| 6 | FuzzyMatcher.php | Likhetssjekk for fritekst | 🟡 Middels |
| 7 | BotEngine.php | Sett sammen hele pipeline | 🔵 Avansert |
| 8 | chatbot.php | API som kaller BotEngine | 🟢 Lett |
| 9 | index.html | UI + fetch() + JSON-visning | 🟢 Lett |
| 10 | admin/* (valgfritt) | CRUD og visning av samtaler | 🔵 Avansert |

---

## **5) Sikkerhet og kvalitet**

- All SQL kjøres via **prepared statements** ($pdo->prepare())
- Input sjekkes (lengde, tegn, tomme meldinger)
- Output HTML-escapes (htmlspecialchars())
- UTF-8 hele veien
- Håndter feil med try/catch
- Loggfør samtaler og feil
- Unngå bruk av eval() og ufiltrerte $_REQUEST-variabler

---

## **6) Testplan (enkel men tydelig)**

### **Enhetstester (kan gjøres manuelt i PHP)**

| **Klasse** | **Testmål** |
| --- | --- |
| RuleMatcher | Treffer riktig på nøkkelord |
| FullTextSearch | Returnerer riktig rad fra DB |
| FuzzyMatcher | Treffer på lignende tekst |
| BotEngine | Returnerer forventet svar for kjent spørsmål |

### **Integrasjonstester**

- POST /api/chatbot.php med kjent spørsmål → får riktig JSON
- Ukjent spørsmål → får fallback
- Database nede → håndteres uten feilside

---

## **7) Relevante PHP-dokumenter**

| **Tema** | **Dokumentasjon** |
| --- | --- |
| PDO | [php.net/manual/en/book.pdo.php](https://www.php.net/manual/en/book.pdo.php) |
| Prepared Statements | [php.net/manual/en/pdo.prepared-statements.php](https://www.php.net/manual/en/pdo.prepared-statements.php) |
| FULLTEXT Search (MySQL) | [dev.mysql.com/doc/refman/8.0/en/fulltext-search.html](https://dev.mysql.com/doc/refman/8.0/en/fulltext-search.html) |
| similar_text() | [php.net/manual/en/function.similar-text.php](https://www.php.net/manual/en/function.similar-text.php) |
| levenshtein() | [php.net/manual/en/function.levenshtein.php](https://www.php.net/manual/en/function.levenshtein.php) |

---

## **8) Bonusforslag (hvis tid)**

- Logg “ubestemte” spørsmål i egen tabell → vis i admin-siden
- Lag REST-endepunkt /api/conversations for oversikt
- Legg til cache med $_SESSION eller Redis (Docker)
- Implementer rate limiting (f.eks. maks 10 requests/min per IP)

---

Vil du at jeg nå lager en **README.md**-fil basert på denne norske planen — klar til bruk i prosjektmappen din, med ferdig formatterte seksjoner og kodemaler (PDO, schema.sql og første BotEngine-mal)?