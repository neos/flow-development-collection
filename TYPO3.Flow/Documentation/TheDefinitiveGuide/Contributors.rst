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
* Bastian Waidelich
* Benno Weinzierl
* Berit Jensen
* Bernhard Fischer
* Cedric Ziel
* Christian Jul Jensen
* Christian Kuhn
* Christian Müller
* Christopher Hlubek
* Dominique Feyer
* Felix Oertel
* Ferdinand Kuhl
* Franz Kugelmann
* Helmut Hummel
* Henrik Møller Rasmussen
* Ingo Pfennigstorf
* Irene Höppner
* Jacob Floyd
* Jan-Erik Revsbech
* Jochen Rau
* Johannes Künsebeck
* Julian Kleinhans
* Julian Wachholz
* Karol Gusak
* Karsten Dambekalns
* Lars Peipmann
* Laurent Cherpit
* Lienhart Woitok
* Marc Neuhaus
* Marco Huber
* Markus Goldbeck
* Markus Günther
* Martin Brüggemann
* Martin Ficzel
* Mattias Nilsson
* Michael Gerdemann
* Michael Klapper
* Michael Sauter
* Oliver Hader
* Pankaj Lele
* Patrick Pussar
* Philipp Maier
* Rens Admiraal
* Robert Lemke
* Ryan J. Peterson
* Sascha Egerer
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
