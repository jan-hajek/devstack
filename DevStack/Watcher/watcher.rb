#!/usr/bin/env ruby

################### shell functions ######################

def greenText(string) 
	system "printf \"\e[1;32m#{string}\e[0m\""
end

def redText(string) 
	system "printf \"\e[1;31m#{string}\e[0m\""
end

def yellowLine(string) 
	system "printf \"\x1b[30;43m\x1b[2K#{string}\n\x1b[0m\x1b[2K\""
end

def greenLine(string) 
	system "printf \"\x1b[30;42m\x1b[2K#{string}\n\x1b[0m\x1b[2K\""
end

def redLine(string) 
	system "printf \"\x1b[37;41m\x1b[2K#{string}\n\x1b[0m\x1b[2K\""
end

def shellCommandExists(command) 
	output = `command -v #{command} 2>&1`
	return !output.empty?
end

################### functions ######################

def getConfig(configPath, configSection) 
	require 'yaml'
	config = YAML.load_file(configPath)
	if(config[configSection].nil?)
		redText "config section named #{configSection} missing"
		puts ", available: \n"
		config.each { |sectionName, params|
			puts "#{sectionName}\n"
		}
		exit 1
	end
	config = config[configSection]
	return config
end

def getWatchers(config) 
	return config['watchers'].select { |name, params|
		params['disable'].nil? || params['disable'] == false
	}
end

def createFindScript(currentDir, config) 
	patterns = Array.new
	config['watchers'].each { |name, params|
		patterns.push("\\(#{params['pattern']}\\)") 
	}
	patterns = patterns.uniq{|x| x}
	
	excluded = Array.new
	config['excluded'].split(' ').each { |name|
		excluded.push("! -path '#{name}'") 
	}
	
	return "find #{currentDir} #{excluded.join(' ')} -regex '#{patterns.join('\\|')}'"
end

def printDebugInfo(findScript, watchers)
	print "\n"
	yellowLine "! debug mode !"
	print "\n"
	
    print "find script\n"
	print "    #{findScript}\n"
	
	print "watchers\n"
	watchers.each { |name, params|
		print "    #{name} - pattern: "
		print "#{params['pattern']}"
		print ", script: "
		print "#{params['script']}\n"
	}
end

def checkFilesCount(findScript)
	monitoredFilesCount = `#{findScript} | wc -l`

	if (Integer(monitoredFilesCount) == 0)
		redText "\nno files to watch\n\n"
		exit 1
	end
	
	print "\n---------------------\nnumber of monitored files: "
	print "#{monitoredFilesCount}"
end

def prepareScript(script, filePath, projectDir, watcherDir) 
	name = File.basename(filePath)
	basename = File.basename(filePath, File.extname(filePath))
	ext = File.extname(filePath).gsub(".", "")
	dir = File.dirname(filePath)
	
	script = script.gsub("@fileBasename", basename)
	script = script.gsub("@fileDir", dir)
	script = script.gsub("@fileExt", ext)
	script = script.gsub("@fileName", name)
	script = script.gsub("@filePath", filePath)
	script = script.gsub("@projectDir", projectDir)
	script = script.gsub("@watcherDir", watcherDir)
		
	return script
end

################### args ######################

watcherDir = File.expand_path(File.dirname(__FILE__))
currentDir = './'
configSection = nil
configPath = "#{watcherDir}/config.yaml"

require 'getoptlong'
opts = GetoptLong.new(
  [ '--help', '-h', GetoptLong::NO_ARGUMENT ],
  [ '--section', GetoptLong::REQUIRED_ARGUMENT ],
  [ '--config', GetoptLong::OPTIONAL_ARGUMENT ]
)
opts.each do |opt, arg|
	case opt
		when '--help'
      		print "Usage: watcher.rb --config=<configPath> --section=<configSection>\n"
	      	print "Yaml config options: (use space to indent, tabs are not allowed)\n"
	      	print "coming soon ..., check example in config.yaml\n"
			exit
    	when '--section'
      		configSection = arg
    	when '--config'
			configPath = arg
	end
end

################### check ######################

if (configSection == nil)
	redText "config section is empty, use watcher.rb --section\n"
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

puts "\nConfiguration read from #{configPath}\n\n"

config = getConfig configPath, configSection
debug = config['debug'].nil? ? false : config['debug']
watchers = getWatchers config

findScript = createFindScript currentDir, config



print "\nproject name: #{config['projectName']}"

if(debug)
	printDebugInfo findScript,watchers
end

loop do
	checkFilesCount findScript
	
	print "waiting for change ... \n"
	begin
		path= `inotifywait --format "%w" -qre modify,delete,create,move #{"`#{findScript}`"}`
	rescue Interrupt => e
		begin
			puts "\nPress to continue"
			answer = gets.chomp
		rescue Interrupt => e
			puts "\n"
			exit
		end 
		next
	end
	
	path = path.strip
	
	print "change in\n"
	print "    #{path}\n"

	isValid = true

	print "used watchers\n"
	watchers.each { |watcherName, params|
		if ( /#{params['pattern']}/.match(path) )
			print "    #{watcherName}"
			
			script = prepareScript params['script'], path, currentDir, watcherDir

			if(debug)
				print " - script: ";	print "#{script}\n"
			else
				output=`#{script}`  
				result=$?.success?
				
				print ", result: "
				
				if(result)
					print "OK\n"
				else
					isValid = false
					redText "FAILURES\n"
				end

				
				outputAlways = params['outputAlways'].nil? ? false : params['outputAlways']
				if(outputAlways || !result)
					print "\n\n-----------------------------------------------------\n"
					puts output
					print "-----------------------------------------------------\n\n\n"
				end
			end
		end
	}
	
	print "\n"
	
	if(!debug)
		if(isValid)
			greenLine "OK"
		else
			redLine "FAILURES!"
		end
	end
end