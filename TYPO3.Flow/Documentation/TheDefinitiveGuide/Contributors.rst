============
Contributors
============

The following is a list of contributors generated from version control
information (see below). As such it is neither claiming to be complete nor is the
ordering anything but alphabetic.

* Adrian Föder
* Aftab Naveed
* Alexander Berl
* Alexander Schnitzler
* Alexander Stehlik
* Andreas Förthner
* Andreas Wolf
* Andy Grunwald
* Aske Ertmann
* Bastian Heist
* Bastian Waidelich
* Benno Weinzierl
* Berit Jensen
* Bernhard Fischer
* Carsten Bleicker
* Cedric Ziel
* Christian Jul Jensen
* Christian Kuhn
* Christian Müller
* Christopher Hlubek
* Dan Untenzu
* Daniel Lienert
* Dmitri Pisarev
* Dominique Feyer
* Felix Oertel
* Ferdinand Kuhl
* Franz Kugelmann
* Georg Ringer
* Helmut Hummel
* Henrik Møller Rasmussen
* Ingo Pfennigstorf
* Irene Höppner
* Jacob Floyd
* Jan-Erik Revsbech
* Jochen Rau
* Johannes Künsebeck
* Jonas Renggli
* Julian Kleinhans
* Julian Wachholz
* Karol Gusak
* Karsten Dambekalns
* Kerstin Huppenbauer
* Lars Peipmann
* Laurent Cherpit
* Lienhart Woitok
* Marc Neuhaus
* Marco Huber
* Markus Goldbeck
* Markus Günther
* Martin Brüggemann
* Martin Ficzel
* Martin Helmich
* Mattias Nilsson
* Michael Gerdemann
* Michael Klapper
* Michael Sauter
* Oliver Hader
* Oliver Eglseder
* Pankaj Lele
* Patrick Pussar
* Philipp Maier
* Rafael Kähm
* Rens Admiraal
* Robert Lemke
* Roland Waldner
* Ryan J. Peterson
* Sascha Egerer
* Sascha Nowak
* Sebastian Helzle
* Sebastian Heuer
* Sebastian Kurfürst
* Simon Schaufelberger
* Simon Schick
* Soeren Rohweder
* Soren Malling
* Stefan Neufeind
* Steffen Ritter
* Stephan Schuler
* Thomas Hempel
* Thomas Layh
* Tim Eilers
* Tim Kandel
* Tim Spiekerkötter
* Tobias Liebig
* Tolleiv Nietsch
* Tymoteusz Motylewski
* Wouter Wolters
* Xavier Perseguers
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
