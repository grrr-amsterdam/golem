=begin
This script monkey-patches Capistrano's methods, set(), server() and task().
It makes them collect the values into the @settings hash so that we 
can use them in the PHP golem script.
Used by Golem_Cli_Command_Ssh

Usage:
ruby ssh_helper.rb /application/configs/deploy.rb
=end

require 'json'
@settings = {}
@curr_env = ''

def set(key, value)
	if @curr_env == ''
		return
	end
	if !@settings[@curr_env]
		@settings[@curr_env] = Hash.new
	end
	@settings[@curr_env][key] = value
end

def server(*args)
	set :server, args[0]
end

def task(env)
	@curr_env = env
	yield
end

require ARGV[0]

print JSON.generate @settings
