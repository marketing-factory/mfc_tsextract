Typoscript extrahieren:
1. in den Seiteneingeschaften des Teilbaums der extrahiert werden soll muss im TSConfig folgendes eingetragen werden:
	mod.web_txmfctsextract.start = 1
	mod.web_txmfctsextract.path = fileadmin/admin/{main|projektname}/templates_ts/
2. Pfad um Fileadmin anlegen
3. Scheduler Task anlegen mit Auswahl des Teilbaums
4. Task ausführen
5. master_setup.ts und master_constants öffnen und "Shortcut"-Templates Inkludierung entfernen und eventuell doppelte Script am Ende entfernen
6. setup_"shortcut".ts constats_"shortcut".ts bearbeiten und "ext"-Templates aus den Master Dateien verlagern
7. master und shortcut Templates in die jeweiligen Datenbank Templates einbinden
8. ID Liste der NICHT auszublenden Typoscript Datensätze erfassen und Query für die Technik erstellen um alle anderen Datensätze auszublenden (update sys_template set hidden = 1 where uid not in (UID1, UID2,...);)
