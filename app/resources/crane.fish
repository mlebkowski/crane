# fish completion for crane

function __fish_crane_projects
	jq -r keys[] < ~/.crane/config.json
end

function __fish_crane_commands
	crane list --format=json | jq -r '.commands[] | .name + "\t" + .description' | grep -v -e '^list' -e '^help'
end;

function __fish_crane_command_help
	crane $argv[1] --help --format=json
end

function __fish_crane_get_arguments
	__fish_crane_command_help $argv[1] | jq -r '.definition.arguments[].name'
end

function __fish_crane_get_options
	__fish_crane_command_help $argv[1] |  jq -r '.definition.options[] | .name + "\t" + .description' | grep -v -e '--help' -e '--no-ansi' -e '--ansi' -e '--no-interaction' -e '--interaction' -e '--version' | cut -b 3-
end

function __fish_crane_using_command
  set cmd (commandline -opc)
  if [ (count $cmd) -gt 1 ]
    if [ $argv[1] = $cmd[2] ]
      return 0
    end
  end
  return 1
end

function __fish_crane_needs_command
  set cmd (commandline -opc)
  if [ (count $cmd) -eq 1 -a $cmd[1] = 'crane' ]
    return 0
  end
  return 1
end


__fish_crane_commands | grep -ve '^list' -e '^help' | while read command description;
	complete -f -c crane -n '__fish_crane_needs_command' -a $command --description "$description"

	__fish_crane_get_arguments $command | while read name;
		if [ $name = "name" ]
			complete -f -c crane -n "__fish_crane_using_command $command" -a "(__fish_crane_projects)"
		end
	end

	__fish_crane_get_options $command | while read option description;
		complete -f -c crane -n "__fish_crane_using_command $command" -l $option -d $description
	end

end

