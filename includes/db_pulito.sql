CREATE DATABASE IF NOT EXISTS `ristorante` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ristorante`;

CREATE TABLE `allergene` (
  `ID_Allergene` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`ID_Allergene`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `utente` (
  `ID_Utente` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  PRIMARY KEY (`ID_Utente`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `carrello` (
  `ID_Carrello` int NOT NULL AUTO_INCREMENT,
  `ID_Utente` int DEFAULT NULL,
  PRIMARY KEY (`ID_Carrello`),
  KEY `ID_Utente` (`ID_Utente`),
  CONSTRAINT `carrello_ibfk_1` FOREIGN KEY (`ID_Utente`) REFERENCES `utente` (`ID_Utente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categoria` (
  `ID_Categoria` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ID_Categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `gruppo` (
  `ID_Gruppo` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`ID_Gruppo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `servizio` (
  `ID_Servizio` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) DEFAULT NULL,
  `Descrizione` text,
  PRIMARY KEY (`ID_Servizio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `gruppo_servizio` (
  `ID_Gruppo` int NOT NULL,
  `ID_Servizio` int NOT NULL,
  PRIMARY KEY (`ID_Gruppo`,`ID_Servizio`),
  KEY `ID_Servizio` (`ID_Servizio`),
  CONSTRAINT `gruppo_servizio_ibfk_1` FOREIGN KEY (`ID_Gruppo`) REFERENCES `gruppo` (`ID_Gruppo`),
  CONSTRAINT `gruppo_servizio_ibfk_2` FOREIGN KEY (`ID_Servizio`) REFERENCES `servizio` (`ID_Servizio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ingrediente` (
  `ID_Ingrediente` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`ID_Ingrediente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menu` (
  `ID_Menu` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) DEFAULT NULL,
  `Descrizione` text,
  PRIMARY KEY (`ID_Menu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menu_carrello` (
  `ID_Menu` int NOT NULL,
  `ID_Carrello` int NOT NULL,
  PRIMARY KEY (`ID_Menu`,`ID_Carrello`),
  KEY `ID_Carrello` (`ID_Carrello`),
  CONSTRAINT `menu_carrello_ibfk_1` FOREIGN KEY (`ID_Menu`) REFERENCES `menu` (`ID_Menu`),
  CONSTRAINT `menu_carrello_ibfk_2` FOREIGN KEY (`ID_Carrello`) REFERENCES `carrello` (`ID_Carrello`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ordinazione` (
  `ID_Ordinazione` int NOT NULL AUTO_INCREMENT,
  `ID_Utente` int DEFAULT NULL,
  `Data` datetime DEFAULT NULL,
  `Stato` enum('In consegna','In preparazione','Consegnato') DEFAULT NULL,
  `Costo` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`ID_Ordinazione`),
  KEY `ID_Utente` (`ID_Utente`),
  CONSTRAINT `ordinazione_ibfk_1` FOREIGN KEY (`ID_Utente`) REFERENCES `utente` (`ID_Utente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menu_ordinazione` (
  `ID_Menu` int NOT NULL,
  `ID_Ordinazione` int NOT NULL,
  PRIMARY KEY (`ID_Menu`,`ID_Ordinazione`),
  KEY `ID_Ordinazione` (`ID_Ordinazione`),
  CONSTRAINT `menu_ordinazione_ibfk_1` FOREIGN KEY (`ID_Menu`) REFERENCES `menu` (`ID_Menu`),
  CONSTRAINT `menu_ordinazione_ibfk_2` FOREIGN KEY (`ID_Ordinazione`) REFERENCES `ordinazione` (`ID_Ordinazione`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prenotazione` (
  `ID_Prenotazione` int NOT NULL AUTO_INCREMENT,
  `ID_Utente` int DEFAULT NULL,
  `Data` date DEFAULT NULL,
  `Ora` time DEFAULT NULL,
  `Persone` int DEFAULT NULL,
  `Stato` enum('In sospeso','Accettata','Rifiutata') DEFAULT NULL,
  PRIMARY KEY (`ID_Prenotazione`),
  KEY `ID_Utente` (`ID_Utente`),
  CONSTRAINT `prenotazione_ibfk_1` FOREIGN KEY (`ID_Utente`) REFERENCES `utente` (`ID_Utente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prodotto` (
  `ID_Prodotto` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) DEFAULT NULL,
  `Prezzo` decimal(10,2) DEFAULT NULL,
  `ID_Categoria` int DEFAULT NULL,
  `Descrizione` text,
  PRIMARY KEY (`ID_Prodotto`),
  KEY `ID_Categoria` (`ID_Categoria`),
  CONSTRAINT `prodotto_ibfk_1` FOREIGN KEY (`ID_Categoria`) REFERENCES `categoria` (`ID_Categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prodotti_allergeni` (
  `ID_Prodotto` int NOT NULL,
  `ID_Allergene` int NOT NULL,
  PRIMARY KEY (`ID_Prodotto`,`ID_Allergene`),
  KEY `ID_Allergene` (`ID_Allergene`),
  CONSTRAINT `prodotti_allergeni_ibfk_1` FOREIGN KEY (`ID_Prodotto`) REFERENCES `prodotto` (`ID_Prodotto`),
  CONSTRAINT `prodotti_allergeni_ibfk_2` FOREIGN KEY (`ID_Allergene`) REFERENCES `allergene` (`ID_Allergene`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prodotti_carrello` (
  `ID_Prodotto` int NOT NULL,
  `ID_Carrello` int NOT NULL,
  `Quantità` int DEFAULT NULL,
  PRIMARY KEY (`ID_Prodotto`,`ID_Carrello`),
  KEY `ID_Carrello` (`ID_Carrello`),
  CONSTRAINT `prodotti_carrello_ibfk_1` FOREIGN KEY (`ID_Prodotto`) REFERENCES `prodotto` (`ID_Prodotto`),
  CONSTRAINT `prodotti_carrello_ibfk_2` FOREIGN KEY (`ID_Carrello`) REFERENCES `carrello` (`ID_Carrello`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prodotti_ingredienti` (
  `ID_Prodotto` int NOT NULL,
  `ID_Ingrediente` int NOT NULL,
  PRIMARY KEY (`ID_Prodotto`,`ID_Ingrediente`),
  KEY `ID_Ingrediente` (`ID_Ingrediente`),
  CONSTRAINT `prodotti_ingredienti_ibfk_1` FOREIGN KEY (`ID_Prodotto`) REFERENCES `prodotto` (`ID_Prodotto`),
  CONSTRAINT `prodotti_ingredienti_ibfk_2` FOREIGN KEY (`ID_Ingrediente`) REFERENCES `ingrediente` (`ID_Ingrediente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prodotti_menu` (
  `ID_Prodotto` int NOT NULL,
  `ID_Menu` int NOT NULL,
  PRIMARY KEY (`ID_Prodotto`,`ID_Menu`),
  KEY `ID_Menu` (`ID_Menu`),
  CONSTRAINT `prodotti_menu_ibfk_1` FOREIGN KEY (`ID_Prodotto`) REFERENCES `prodotto` (`ID_Prodotto`),
  CONSTRAINT `prodotti_menu_ibfk_2` FOREIGN KEY (`ID_Menu`) REFERENCES `menu` (`ID_Menu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prodotti_ordinazione` (
  `ID_Prodotto` int NOT NULL,
  `ID_Ordinazione` int NOT NULL,
  `Quantità` int DEFAULT NULL,
  PRIMARY KEY (`ID_Prodotto`,`ID_Ordinazione`),
  KEY `ID_Ordinazione` (`ID_Ordinazione`),
  CONSTRAINT `prodotti_ordinazione_ibfk_1` FOREIGN KEY (`ID_Prodotto`) REFERENCES `prodotto` (`ID_Prodotto`),
  CONSTRAINT `prodotti_ordinazione_ibfk_2` FOREIGN KEY (`ID_Ordinazione`) REFERENCES `ordinazione` (`ID_Ordinazione`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promozione` (
  `ID_Promozione` int NOT NULL AUTO_INCREMENT,
  `Descrizione` text,
  `Sconto` decimal(5,2) DEFAULT NULL,
  `GiornoSettimana` enum('Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato','Domenica') DEFAULT NULL,
  PRIMARY KEY (`ID_Promozione`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recensione` (
  `ID_Recensione` int NOT NULL AUTO_INCREMENT,
  `ID_Utente` int DEFAULT NULL,
  `ID_Prodotto` int DEFAULT NULL,
  `Testo` text,
  `Voto` int DEFAULT NULL,
  `Data` date DEFAULT NULL,
  PRIMARY KEY (`ID_Recensione`),
  KEY `ID_Utente` (`ID_Utente`),
  KEY `ID_Prodotto` (`ID_Prodotto`),
  CONSTRAINT `recensione_ibfk_1` FOREIGN KEY (`ID_Utente`) REFERENCES `utente` (`ID_Utente`),
  CONSTRAINT `recensione_ibfk_2` FOREIGN KEY (`ID_Prodotto`) REFERENCES `prodotto` (`ID_Prodotto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `utente_gruppo` (
  `ID_Utente` int NOT NULL,
  `ID_Gruppo` int NOT NULL,
  PRIMARY KEY (`ID_Utente`,`ID_Gruppo`),
  KEY `ID_Gruppo` (`ID_Gruppo`),
  CONSTRAINT `utente_gruppo_ibfk_1` FOREIGN KEY (`ID_Utente`) REFERENCES `utente` (`ID_Utente`),
  CONSTRAINT `utente_gruppo_ibfk_2` FOREIGN KEY (`ID_Gruppo`) REFERENCES `gruppo` (`ID_Gruppo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;