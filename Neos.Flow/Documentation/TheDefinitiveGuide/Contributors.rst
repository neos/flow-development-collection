============
Contributors
============

The following is a list of contributors generated from version control
information (see below). As such it is neither claiming to be complete nor is the
ordering anything but alphabetic.

* Adrian Föder
* Aftab Naveed
* Alexander Berl
* Alex Fruehwirth
* Alexander Kleine-Börger
* Alexander Schnitzler
* Alexander Stehlik
* Andreas Förthner
* Andreas Wolf
* Andy Grunwald
* Aske Ertmann
* Aslambek Idrisov
* Bastian Heist
* Bastian Waidelich
* Benno Weinzierl
* Berit Jensen
* Bernhard Fischer
* Bernhard Schmitt
* Carsten Bleicker
* Carsten Blüm
* Cedric Ziel
* Christian Herberger
* Christian Jul Jensen
* Christian Kuhn
* Christian Müller
* Christian Vette
* Christian Wenzel
* Christoph Daehne
* Christopher Hlubek
* Dan Untenzu
* Daniel Lienert
* Daniel Siepmann
* Daniela Grammlich
* David Kartik
* David Sporer
* David Vogt
* DavidSporer
* Denny Lubitz
* Dmitri Pisarev
* Doehring Daniel
* Dominique Feyer
* Felix Oertel
* Ferdinand Kuhl
* Florian Heinze
* Florian Kaiser
* Franz Kugelmann
* Frederik Löffert
* Fritjof Bohm
* Georg Ringer
* Gerhard Boden
* Hans Höchtl
* Helfer Dominique
* Helmut Hummel
* Henrik Møller Rasmussen
* Ingo Pfennigstorf
* Irene Höppner
* Ivan Litovchenko
* Jacob Floyd
* Jan Hinzmann
* Jan-Erik Revsbech
* Joachim Mathes
* Jochen Rau
* Johannes Hertenstein
* Johannes Künsebeck
* Johannes Steu
* Jon Klixbüll Langeland
* Jon Uhlmann
* Jonas Renggli
* Julian Kleinhans
* Julian Wachholz
* Kai Möller
* Kai Szymanski
* Karol Gusak
* Karsten Dambekalns
* Kay Strobach
* Kerstin Huppenbauer
* Knallcharge
* Lars Lauger
* Lars Peipmann
* Laurent Cherpit
* Leon Kleffmann
* Lienhart Woitok
* Lisa Kampert
* Lorenz Ulrich
* Lucas Krause
* Malte Muth
* Malte Riechmann
* Marc Neuhaus
* Marcin Ryzycki
* Marcin Sągol
* Marco Huber
* Marcos Bjoerkelund
* Markus Goldbeck
* Markus Günther
* Markus Sommer
* Martin Bless
* Martin Brüggemann
* Martin Ficzel
* Martin Helmich
* Mattias Nilsson
* Max Strübing
* Michael Gerdemann
* Michael Klapper
* Michael Sauter
* Mirjam Bornschein
* Moritz Spindelhirn
* Narongsak Mala
* Nicola Hauke
* Nicolas Hoeller
* Oliver Eglseder
* Oliver Hader
* Pankaj Lele
* Patrick Pussar
* Paul Weiske
* Philipp Maier
* Rafael Kähm
* Raffael Comi
* Ralf Kühnel
* Rens Admiraal
* René Pflamm
* Robert Lemke
* Robin Krahnen
* Roland Waldner
* Roman Minchyn
* Ryan J. Peterson
* Rémy DANIEL
* Salvatore Eckel
* Sascha Egerer
* Sascha Löffler
* Sascha Nowak
* Sebastian Helzle
* Sebastian Heuer
* Sebastian Kurfürst
* Sebastian Sommer
* Simon Schaufelberger
* Simon Schick
* Soren Malling
* Stefan Neufeind
* Steffen Ritter
* Stephan Schuler
* Sören Rohweder
* Søren Malling
* Thomas Blaß
* Thomas Buhk
* Thomas Hempel
* Thomas Layh
* Tim Eilers
* Tim Kandel
* Tim Spiekerkötter
* Tobias Liebig
* Tolleiv Nietsch
* Torsten Blindert
* Troels Thrane
* Tymoteusz Motylewski
* Vaclav Janoch
* Veikko Skurnik
* Wilhelm Behncke
* Wouter Wolters
* WouterJ
* Xavier Perseguers
* Y. Mayer
* Yuri Zaveryukha
* Zach Davis

The list has been generated with some manual tweaking of the output of this::

  rm contributors.txt
  for REPO in `ls` ; do
    cd $REPO
    git log --format='%aN' >> ../contributors.txt
    cd ..
  done
  sort -u < contributors.txt > contributors-sorted.txt
  mv contributors-sorted.txt contributors.txt
