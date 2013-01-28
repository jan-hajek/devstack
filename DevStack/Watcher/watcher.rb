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

################### functions ######################

def getConfig(configPath, configSection) 
	require 'yaml'
	config = YAML.load_file(configPath)
	if(config[configSection].nil?)
		redText "config section named #{configSection} missing\n"
		exit 1
	end
	config = config[configSection]
	return config
end

def createFindScript(currentDir, config) 
	extensions = Array.new
	config['watchers'].each { |name, params|
		extensions.push(params['ext']) 
	}
	extensions = extensions.uniq{|x| x}
	
	excluded = Array.new
	config['excluded'].split(' ').each { |name|
		excluded.push("! -path '#{name}'") 
	}
	
	return "find #{currentDir} #{excluded.join(' ')} -regex '.*\\.\\(#{extensions.join('\\|')}\\)'"
end

def printDebugInfo(findScript, watchers)
	redText " ! debug mode !\n"
	
    print "find script: "
	greenText "#{findScript}\n"
	
	print "watchers:\n"
	watchers.each { |name, params|
		print "    #{name} - ext: "
		greenText "#{params['ext']}"
		print ", script: "
		greenText "#{params['script']}\n"
	}
end

def checkFilesCount(findScript)
	monitoredFilesCount = `#{findScript} | wc -l`

	if (monitoredFilesCount == 0)
		redtext "no files to watch\n"
		exit 1
	end
	
	print "\n---------------------\nnumber of monitored files: "
	greenText "#{monitoredFilesCount}"
end

def prepareScript(script, filePath, projectDir, watcherDir) 
	name = File.basename(filePath)
	basename = File.basename(filePath, File.extname(filePath))
	ext = File.extname(filePath).gsub(".", "")
	dir = File.dirname(filePath)
	
	script = script.gsub("%fileBasename%", basename)
	script = script.gsub("%fileDir%", dir)
	script = script.gsub("%fileExt%", ext)
	script = script.gsub("%fileName%", name)
	script = script.gsub("%filePath%", filePath)
	script = script.gsub("%projectDir%", projectDir)
	script = script.gsub("%watcherDir%", watcherDir)
		
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

config = getConfig configPath, configSection
debug = config['debug'].nil? ? false : config['debug']
watchers = config['watchers']

findScript = createFindScript currentDir, config

greenText "\nproject name: #{config['projectName']}"
if(debug)
	printDebugInfo findScript,watchers
end

loop do
	checkFilesCount findScript
	
	print "waiting for change ... \n"
	begin
		path= `inotifywait --format "%w" -qre modify,delete,create,move #{"`#{findScript}`"}`
	rescue Interrupt => e
		redText "\ninterrupted\n"
		exit
	end
	
	path = path.strip
	ext = File.extname(path).gsub(".", "")
	
	print "change in: "
	greenText "#{path}\n"

	watchers.each { |watcherName, params|
		if ( ext.eql? params['ext'])
			print "used watcher: #{watcherName}"
			
			script = prepareScript params['script'], path, currentDir, watcherDir

			if(debug)
				print "- script: ";	greenText "#{script}\n"
			else
				output=`#{script}`  
				result=$?.success?
				if(result)
					greenText " ok\n"
				else
					redText " error\n"
				end
				
				outputAlways = params['outputAlways'].nil? ? false : params['outputAlways']
				if(outputAlways || !result)
					puts output
				end
			end
		end
	}
end