#!/bin/bash

################# format functions ####################											   
function pause(){
   read -p "== press [ENTER] to continue =="
}

function printSeparator() {
	printf "%*s" $(tput cols) |tr " " "="
}

function printLine() {
	printf "$1"
	breakLine
}

function breakLine() {
	printf "\n"
}

function successColor() 
{
	printf "\e[1;32m"
	printf "$1" 
	printf "\e[0m"
}

function errorColor() 
{
	printf "\e[1;31m"
	printf "$1"
	printf "\e[0m"
}


#######################################################

command -v inotifywait >/dev/null 2>&1 || {
	errorColor "Inotify tools are required.";
	breakLine 
	exit 1; 
}

watched_dir=./
run_script="phpunit -c ./tests"
debug=false
excluded=''

while :
do
    case "$1" in
      -e | --excluded)
      	if [[ -z "$2" ]]
			then
		    echo "argument --watched_dir required value"
		    exit 1
		fi
		excluded=$excluded' ! -path "'$2'"'
		shift 2
	  ;;
	  
	  --watched_dir)
	  	if [[ -z "$2" ]]
			then
		    echo "argument --watched_dir required value"
		    exit 1
		fi
			 
      	watched_dir=$2
		shift 2
	  ;;
	  	  
	  --run_script)
	  	if [[ -z "$2" ]]
			then
		    echo "argument --watched_dir required value"
		    exit 1
		fi
      	run_script=$2
		shift 2
	  ;;
	  
	  --debug)
      	debug=true
		shift
	  ;;
 
	  --help)
      	printLine "Usage: watcher.sh [opts]"
      	printLine "Options:"
      	line="     %-30s %s \n"
      	printf "$line" "-e, --excluded <patern>"	"example: /home/jelito/watcher.sh -e './testStack/Mocker/*'"
      	printf "$line" "--watched_dir <dir>" 		"main directory, default is actual"
      	printf "$line" "--run_script <string>" 	"script executed after file change, default: phpunit -c ./tests"
      	printf "$line" "--debug" 					"start in debug mode, with aditional info"
      	exit 0
	  ;;
	  	
	  -*)
	  	echo "Error: Unknown option: $1" >&2
	  	exit 1
	  ;;
	    
	  *)  # No more options
		break
	  ;;
	  
      --) # End of all options
		shift
	  break;     
    esac
done

if [ ! -d "$watched_dir" ] 
then
    echo "directory $watched_dir not found, use argument --watched_dir"
    exit 1
fi
        
while :; do	
	find_script_print="find $watched_dir $excluded -type f -name '*.php' -print";	
	find_script_count=$(eval $find_script_print | wc -l)     
	files=$(eval $find_script_print)
	
	if [ $debug == true ]
	then
		breakLine
		errorColor "! debug mode !"
	    breakLine
	    breakLine
	    printf "run script: "
		successColor "$run_script"
		breakLine
		breakLine
		printf "find script: "
		successColor "$find_script_print"
		breakLine
		breakLine
		printLine "files:"
		eval $find_script_print
		breakLine
	fi								

	breakLine
	
	if [ $find_script_count == 0 ]
	then
		errorColor "no files to watch"
		breakLine
		exit 1
	fi								

	
	successColor "waiting for change, number of monitored files: $find_script_count"
	breakLine				
	inotifywait -qre modify,delete,create,move $files
	errorColor "run script"
	breakLine
	$run_script 
done