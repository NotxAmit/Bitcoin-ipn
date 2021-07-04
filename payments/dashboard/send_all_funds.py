from bit import Key
import argparse

parser = argparse.ArgumentParser(description="Check balance from the private key wallet and if it has send it with minimun fee.")
parser.add_argument("private_key", help="The private key of the wallet you want to check.")
parser.add_argument("address", help="The address to send the money.")

args = parser.parse_args()

private_key = args.private_key
send_address = args.address

wallet = Key(private_key)

wallet.send([], leftover=send_address, fee=1)
