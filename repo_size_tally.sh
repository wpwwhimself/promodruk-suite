#!/bin/bash

#### SETUP ####

UPDATE_MODE=0

#### ARGUMENTS ####

usage () {
  echo "Tracks repo sizes on server"
  echo "-----------------"
  echo "Usage: $0 [OPTIONS]"
  echo "Options:"
  echo "  -h           Show this message"
  echo "  -u           Update mode: adds new data to log before showing it"
}

while getopts ":hu" opt; do
  case $opt in
    h)
      usage
      exit 0
      ;;
    u)
      UPDATE_MODE=1
      ;;
    \?)
      heading "🚨 Unknown argument: $1"
      usage
      exit 1
      ;;
    :)
      heading "🚨 Option $OPTARG requires an argument"
      usage
      exit 1
      ;;
  esac
done

#### START ####

# utwórz plik z logami, jeśli jeszcze go nie ma

if [ ! -f ~/repo_size_tally.log ]; then
  touch ~/repo_size_tally.log
  echo -e "Data\tKwazar\tMagazyn\tOfertownik" >> ~/repo_size_tally.log
fi

# zbierz dane i dopisz je do pliku

if [ $UPDATE_MODE -eq 1 ]; then
  CURRENT_DATE=$(date +"%Y-%m-%d")
  DATA="$(du -lkhd 1 ~/wpww_repos/promodruk-suite | grep -E "kwazar|magazyn|ofertownik" | cut -f1 | awk 'ORS=NR%3 ? " " : "\n"' | column -t)"

  echo -e "$CURRENT_DATE" "\t" "$DATA" >> ~/repo_size_tally.log
fi

# pokaż wyniki

cat ~/repo_size_tally.log | column -t
