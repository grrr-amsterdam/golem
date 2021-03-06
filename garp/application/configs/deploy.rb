#set :log_level, :info
set :linked_dirs, %w{
	public/uploads/documents
	public/uploads/images
	application/data/logs
	application/data/cache/tags
}
set :keep_releases, 2


load "application/configs/deploy.rb"

Dir.glob("garp/deploy/tasks/*.cap").each { |r| load r }
load "garp/deploy/garp3.cap"

set :tmp_dir, "/tmp/#{fetch(:application)}-#{fetch(:stage)}"
