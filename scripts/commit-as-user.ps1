# Create a git commit without Cursor co-author injection (uses commit-tree).
param(
	[Parameter(Mandatory = $true)]
	[string]$Message,
	[string]$RepoPath = (Get-Location).Path,
	[string]$AuthorName = "Kazi Sadib Reza",
	[string]$AuthorEmail = "kazisadibreza@gmail.com"
)

$ErrorActionPreference = "Stop"
Push-Location $RepoPath

$status = git status --porcelain
if (-not $status.Trim()) {
	Write-Host "Nothing to commit in $RepoPath"
	Pop-Location
	exit 0
}

git add -A
$tree = git write-tree
$parent = git rev-parse HEAD 2>$null
$env:GIT_AUTHOR_NAME = $AuthorName
$env:GIT_AUTHOR_EMAIL = $AuthorEmail
$env:GIT_COMMITTER_NAME = $AuthorName
$env:GIT_COMMITTER_EMAIL = $AuthorEmail

if ($parent) {
	$commit = git commit-tree $tree -p $parent -m $Message
} else {
	$commit = git commit-tree $tree -m $Message
}

$branch = git branch --show-current
if ($branch) {
	git update-ref "refs/heads/$branch" $commit
} else {
	git update-ref HEAD $commit
}

Write-Host "Committed $commit on $branch as $AuthorName"
git log -1 --format="%h %an <%ae>%n%s"
Pop-Location
