-- Korrekt tegnsett og tidsone (grunnmur for tekst og tidsstempler)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Kjerne-tabellen for Q&A-par
CREATE TABLE IF NOT EXISTS qa (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,  -- Unik nøkkel (må ha)
  question   TEXT         NOT NULL,                    -- Teksten vi matcher mot (må ha)
  answer     TEXT         NOT NULL,                    -- Svaret vi sender (må ha)
  tags       VARCHAR(255) NULL,                        -- Enkel filtrering/ruting (valgfri, men nyttig)
  is_active  TINYINT(1)   NOT NULL DEFAULT 1,          -- Skrur av/på uten å slette (må ha i praksis)
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enkel samtalelogging (nyttig i utvikling og feilsøking)
CREATE TABLE IF NOT EXISTS chat_log (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- Robust id for mange rader
  user_message  TEXT         NOT NULL,                      -- Hva brukeren sendte (må ha)
  matched_qa_id INT UNSIGNED NULL,                          -- Hvilken QA rad vi svarte fra (om noen)
  answer_sent   TEXT         NULL,                          -- Hva vi faktisk svarte (kan avvike etterhvert)
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chatlog_qa
    FOREIGN KEY (matched_qa_id) REFERENCES qa(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Billig indeks for å filtrere aktive rader
CREATE INDEX idx_qa_active ON qa(is_active);