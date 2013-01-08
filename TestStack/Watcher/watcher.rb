#!/usr/bin/env ruby

################### shell functions ######################

def greenText(string) 
	system "printf \"\e[1;32m#{string}\e[0m\""
end

def redText(string) 
	system "printf \"\e[1;31m#{string}\e[0m\""
end

def shellCommandExists(command) 
	output = `command -v #{command} 2>&1`
	return !output.empty?
end

################### args ######################

ARGV.reverse!
configPath = ARGV.pop
currentDir = './'

while item = ARGV.pop do
	if(item == '--help')
		print "Usage: watcher.rb <configPath>\n"
      	print "Config options: \n"
      	line="     %-30s %s\n"
      	printf "#{line}", "[debug]", "true|false"
      	printf "#{line}", "watchers", "{ext, script}"
		exit
	end
end

################### check ######################

if (configPath == nil)
	redText "config path is empty\n"
	exit
end

if (File.file?(configPath) == false)
	redText "config doesn't exists\n"
	exit
end

if(!shellCommandExists "inotifywait")
	redText "Inotify tools are required\n"
	exit
end

################### run ######################

require 'yaml'
config = YAML.load_file(configPath)
debug = config['debug'].nil? ? false : config['debug']
watchers = config['watchers']

extensions = Array.new
watchers.each { |name, params|
	extensions.push ("-name '*.#{params['ext']}'") 
}

excluded = Array.new
config['excluded'].split(' ').each { |name|
	excluded.push ("! -path '#{name}'") 
}

findScript = "find #{currentDir} #{excluded.join(' ')} -type f #{extensions.join(' -or ')} "

greenText "\nproject name: #{config['projectName']}"
if(debug)
	redText " ! debug mode !\n"
	
    print "find script: "
	greenText "#{findScript}\n"

	#puts findScriptExecutable
	
	print "watchers:\n"
	watchers.each { |name, params|
		print "    #{name} - ext: "
		greenText "#{params['ext']}"
		print ", script: "
		greenText "#{params['script']}\n"
	}
	
end

loop do
	findScriptExecutable = "`#{findScript}`"
	monitoredFilesCount = `#{findScript} | wc -l`
	
	if (monitoredFilesCount == 0)
		redtext "no files to watch\n"
		exit 1
	end
	
	puts "\n---------------------"
	
	print "number of monitored files: "
	greenText "#{monitoredFilesCount}"
	print "waiting for change ... \n"
	
	begin
		path= `inotifywait --format "%w" -qre modify,delete,create,move #{findScriptExecutable}`
	rescue Interrupt => e
		redText "\ninterrupted\n"
		exit
	end
	
	path = path.strip
	name = File.basename(path)
	basename = File.basename(path, File.extname(path))
	ext = File.extname(path).gsub(".", "")
	dir = File.dirname(path)
	
	print "change in: "
	greenText "#{path}\n"

	watchers.each { |name, params|
		if ( ext.eql? params['ext'])
			print "used watcher: "
			greenText "#{name}\n"
			
			script = params['script']
				.gsub("%%basename", basename)
				.gsub("%%dir", dir)
				.gsub("%%ext", ext)
				.gsub("%%name", name)
				.gsub("%%path", path)
			if(debug)
				print "used script: "
				greenText "#{script}\n"
			else
				system script
			end
		end
	}
	
end

