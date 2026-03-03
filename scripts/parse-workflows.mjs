import { readdir, readFile } from 'fs/promises'
import path from 'path'
import YAML from 'yaml'

async function listWorkflowFiles (){
  const dir = path.join(process.cwd(), '.github', 'workflows')
  try{
    const names = await readdir(dir)
    return names.filter(n=>n.endsWith('.yml')||n.endsWith('.yaml')).map(n=>path.join(dir,n))
  }catch(e){
    console.error('No workflows directory found:', e.message)
    return []
  }
}

async function main(){
  const files = await listWorkflowFiles()
  if(files.length===0){
    console.log('No workflow files found.')
    return
  }
  let ok=0, bad=0
  for(const f of files){
    try{
      const text = await readFile(f,'utf8')
      YAML.parse(text)
      console.log(`OK: ${path.relative(process.cwd(), f)}`)
      ok++
    }catch(err){
      console.error(`ERROR: ${path.relative(process.cwd(), f)} -> ${err.message}`)
      bad++
    }
  }
  console.log(`\nSummary: ${ok} OK, ${bad} errors`)
  process.exit(bad>0?2:0)
}

main().catch(e=>{ console.error(e); process.exit(3) })
