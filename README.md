Elektronischer Semester Apparat f�r Stud.IP
-------------------------------------------

Installation:
ESA besteht aus zwei Plugin Klassen, die �ber die Webschnittstelle der
Pluginadministration in Stud.IP installiert werden. Dazu m�ssen alle notwendigen
Dateien in einem zip Archiv zusammengefasst werden. Dieses kann dann direkt �ber
die vorgesehene Schnittstelle hochgeladen werden.


Konfiguration:
Das Systemplugin zur Erfassung der Semesterapparate kann nur von 'root'
Administratoren oder von beliebigen Nutzern, denen die Rolle 'Literaturadmin'
zugewiesen wurde, benutzt werden. Dazu muss man in der Pluginadministration �ber
die Rollenadministration eine entsprechende Rolle erzeugen und Nutzern
zuweisen.
Das Plugin kann �ber folgende Konfigurationseinstellungen gesteuert werden,
diese sind im Stud.IP Administrationsbereich nur f�r 'root' Administratoren
zug�nglich:
ESA_LIT_CATALOG: das K�rzel des f�r dynamische Listen zu benutzenden
Literaturkataloges
ESA_LIT_CATALOG_SEARCH_FIELD: das Z39.50 Attribut, das zur Suche nach 
dynamischen Listen benutzt wird
ESA_LIT_CATALOG_SEARCH_MAX_HITS: die maximale Anzahl an Eintr�gen in einer
dynamischen Literaturliste

